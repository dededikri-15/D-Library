<?php

namespace App\Http\Controllers;

use App\Actions\RecordReading;
use App\Http\Requests\ReadingHistoryRequest;
use App\Models\Book;
use App\Models\ReadingHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingHistoryController extends Controller
{
    public function __construct(protected RecordReading $recordReading)
    {
        //
    }

    public function index(Request $request): View
    {
        $histories = $request->user()
            ->readingHistories()
            ->with('book.category', 'book.author')
            ->latest('last_read_at')
            ->paginate(config('perpustakaan.pagination.per_page'));

        return view('reading-histories.index', ['histories' => $histories]);
    }

    /**
     * Simpan/perbarui posisi baca terakhir milik user yang sedang login
     * (PRD §10, Task 11.6).
     *
     * Penjadwalan dan penjepitan halaman ada di RecordReading, bukan di sini,
     * supaya aturan "halaman tidak boleh melebihi jumlah halaman buku" hanya
     * punya satu implementasi.
     */
    public function store(ReadingHistoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($request->integer('book_id'));

        /*
         * Aturan yang sama seperti membuka pembaca: tidak ada gunanya menyimpan
         * posisi untuk buku yang tidak boleh dibaca. Tanpa cek ini, anggota bisa
         * mengisi daftar "riwayat baca" dengan buku milik orang lain hanya
         * dengan memalsukan POST.
         */
        if (! $book->canBeReadBy($user)) {
            abort(403, __('messages.not_allowed_to_read'));
        }

        $this->recordReading->savePage($user, $book, $request->integer('last_page'));

        $this->notifySelf(
            $user,
            'reading_saved',
            [
                'subject' => $book->title,
                'last_page' => $request->integer('last_page'),
            ],
            route('reading-histories.index'),
        );

        return $this->backWithStatus(__('messages.reading_position_saved'));
    }

    public function destroy(Request $request, ReadingHistory $readingHistory): RedirectResponse
    {
        // Hanya pemilik yang boleh menghapus riwayatnya sendiri.
        abort_unless($readingHistory->user_id === $request->user()?->id, 403);

        $title = $readingHistory->book?->title ?? __('loans.book_deleted');

        $readingHistory->delete();

        $this->notifySelf(
            $request->user(),
            'reading_removed',
            ['subject' => $title],
            route('reading-histories.index'),
        );

        return $this->success('reading-histories.index', __('messages.reading_history_deleted'));
    }
}
