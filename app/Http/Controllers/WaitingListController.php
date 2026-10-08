<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Book;
use App\Models\User;
use App\Models\WaitingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaitingListController extends Controller
{
    use RespondsToAjax;

    /**
     * Daftar buku yang sedang diantre anggota yang login.
     *
     * Data diambil dari `$request->user()`, tidak pernah dari parameter URL
     * — sama seperti riwayat peminjaman, supaya orang tidak bisa melihat
     * antrean orang lain lewat query string.
     */
    public function index(Request $request): View
    {
        $entries = $request->user()
            ->waitingLists()
            ->with(['book.category', 'book.author'])
            ->latest()
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('waiting-lists.index', [
            'entries' => $entries,
            'queueLimit' => (int) config('perpustakaan.waiting_list.max_per_user'),
        ]);
    }

    /**
     * Masuk antrean buku yang sedang habis dipinjam.
     *
     * Aksi ini idempoten seperti favorit: klik kedua tidak membuat baris
     * dobel dan tidak menggandakan jejak aksinya.
     */
    public function store(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($book->status === Book::STATUS_INACTIVE) {
            return $this->reject($request, 'messages.waiting_list_inactive');
        }

        // Sudah mengantre: jawab sukses (bukan error) apa pun keadaan
        // bukunya, supaya klik ganda tidak terlihat seperti kegagalan.
        if ($this->isQueued($user, $book)) {
            return $this->respond(
                $request,
                __('messages.waiting_list_already'),
                $this->payload($user, queued: true),
            );
        }

        if ($book->isAvailable()) {
            return $this->reject($request, 'messages.waiting_list_book_available');
        }

        if ($user->activeLoans()->where('book_id', $book->getKey())->exists()) {
            return $this->reject($request, 'messages.waiting_list_already_borrowing');
        }

        if ($user->waitingLists()->count() >= (int) config('perpustakaan.waiting_list.max_per_user')) {
            return $this->reject($request, 'messages.waiting_list_limit', [
                'max' => (int) config('perpustakaan.waiting_list.max_per_user'),
            ]);
        }

        // updateOrCreate membuat aksi ini idempoten terhadap klik ganda:
        // baris yang sudah ada tidak digandakan oleh unique (user_id, book_id).
        $entry = WaitingList::firstOrCreate([
            'user_id' => $user->getKey(),
            'book_id' => $book->getKey(),
        ]);

        // Klik kedua tidak mengubah apa pun — jangan gandakan jejak aksinya.
        if ($entry->wasRecentlyCreated) {
            $this->notifySelf(
                $user,
                'waiting_list_joined',
                ['subject' => $book->title],
                route('books.show', $book),
            );
        }

        return $this->respond(
            $request,
            __('messages.waiting_list_joined'),
            $this->payload($user, queued: true),
        );
    }

    /**
     * Batalkan antrean milik sendiri.
     *
     * Hanya baris milik user login yang dihapus — URL route ini bisa
     * ditebak orang, jadi tidak ada parameter siapa pun selain bukunya.
     */
    public function destroy(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $deleted = $user->waitingLists()
            ->where('book_id', $book->getKey())
            ->delete();

        // Seperti favorit: pesan jujur soal apa yang benar-benar terjadi,
        // supaya klik ganda tidak menghasilkan dua "berhasil dibatalkan".
        if ($deleted > 0) {
            $this->notifySelf(
                $user,
                'waiting_list_left',
                ['subject' => $book->title],
                route('books.show', $book),
            );
        }

        return $this->respond(
            $request,
            $deleted > 0
                ? __('messages.waiting_list_left')
                : __('messages.waiting_list_missing'),
            $this->payload($user, queued: false),
        );
    }

    private function isQueued(User $user, Book $book): bool
    {
        return $user->waitingLists()->where('book_id', $book->getKey())->exists();
    }

    /**
     * Payload tambahan untuk JS memperbarui tombol tanpa reload.
     *
     * @return array<string, mixed>
     */
    private function payload(User $user, bool $queued): array
    {
        return [
            'queued' => $queued,
            'queue_count' => $user->waitingLists()->count(),
        ];
    }

    /**
     * Tolak aksi dengan pesan sebagai toast merah (redirect) atau JSON
     * bertanda `error` untuk pemanggil fetch().
     *
     * @param  array<string, mixed>  $params
     */
    private function reject(Request $request, string $messageKey, array $params = []): JsonResponse|RedirectResponse
    {
        $message = __($messageKey, $params);

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $message,
                'error' => true,
            ], 422);
        }

        return back()->with('error', $message);
    }
}
