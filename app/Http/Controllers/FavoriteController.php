<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    use RespondsToAjax;

    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->favorites()
            ->with('book.category', 'book.author')
            ->latest()
            ->paginate(config('perpustakaan.pagination.per_page'));

        return view('favorites.index', ['favorites' => $favorites]);
    }

    /**
     * Tambah buku ke favorit milik user yang sedang login.
     *
     * Answered form biasa dialihkan ke halaman sebelumnya dengan flash,
     * sedangkan pemanggil `fetch()` (Task 14.7) menerima JSON. Keduanya
     * memanggil method yang sama persis — tidak ada logika favorit yang
     * ditulis dua kali.
     */
    public function store(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // updateOrCreate membuat aksi ini idempoten: klik dua kali tidak
        // akan melanggar unique constraint (user_id, book_id).
        $favorite = Favorite::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            []
        );

        // Klik kedua tidak mengubah apa pun — jangan gandakan jejak aksinya.
        if ($favorite->wasRecentlyCreated) {
            $this->notifySelf(
                $user,
                'favorite_added',
                ['subject' => $book->title],
                route('books.show', $book),
            );
        }

        return $this->respond(
            $request,
            __('messages.favorite_added'),
            ['is_favorite' => true],
        );
    }

    public function destroy(Request $request, Book $book): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $deleted = $user->favorites()
            ->where('book_id', $book->id)
            ->delete();

        /*
         * Pesannya jujur soal apa yang benar-benar terjadi: request kedua
         * untuk buku yang sudah dihapus tidak diam-diam dilaporkan "berhasil".
         *
         * Tanpa ini, klik ganda pada tombol favorit menghasilkan dua toast
         * "Buku dihapus dari favorit." padahal hanya satu baris yang hilang.
         * `is_favorite` tetap `false` di kedua kasus — itu memang benar,
         * buku sudah tidak ada di favorit — tapi yang perlu dibedakan adalah
         * apa yang terjadi, bukan-keadaan akhirnya.
         */
        if ($deleted > 0) {
            $this->notifySelf(
                $user,
                'favorite_removed',
                ['subject' => $book->title],
                route('books.show', $book),
            );
        }

        return $this->respond(
            $request,
            $deleted > 0
                ? __('messages.favorite_removed')
                : __('messages.favorite_missing'),
            ['is_favorite' => false],
        );
    }
}
