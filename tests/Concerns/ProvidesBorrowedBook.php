<?php

namespace Tests\Concerns;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Helper pembuatan "buku digital yang sedang dipinjam" untuk test pembaca.
 *
 * Dipakai bersama oleh seluruh kelas hasil pemecahan DigitalReadingTest
 * supaya satu-satunya definisi ada di sini — tidak diduplikasi antar file,
 * dan tidak ada kelas yang bergantung pada helper di kelas lain.
 */
trait ProvidesBorrowedBook
{
    /**
     * Buku digital yang sedang dipinjam anggota `$member`.
     */
    protected function borrowedBook(?User $member = null, int $pages = 320): Book
    {
        Storage::fake('local');

        $book = Book::factory()->create([
            'status' => Book::STATUS_BORROWED,
            'pages' => $pages,
        ]);

        UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf')
            ->storeAs('books', $book->id.'.pdf', 'local');

        // Path-nya harus sama dengan yang ada di kolom `file` — kalau tidak,
        // `books.file` menjawab 404 (berkasnya "hilang") dan jalur stream PDF
        // tidak pernah teruji oleh helper ini.
        $book->update(['file' => 'books/'.$book->id.'.pdf']);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => ($member ?? User::factory()->anggota()->create())->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        return $book;
    }
}
