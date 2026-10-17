<?php

namespace Tests\Concerns;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Fixture upload buku & berkas PDF — dipakai bersama oleh kelas hasil
 * pemecahan BookFileUploadTest (payload form buku dan pembuat buku
 * ber-PDF yang sudah ada di disk privat).
 */
trait ProvidesBookUploadFixtures
{
    /**
     * Data dasar yang sah untuk POST /buku.
     *
     * @return array<string, mixed>
     */
    protected function bookPayload(array $overrides = []): array
    {
        $book = Book::factory()->make();

        return array_merge([
            'title' => 'Buku Tanpa Berkas',
            'isbn' => '978-602-111-111-1',
            'publication_year' => 2024,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
            'status' => Book::STATUS_AVAILABLE,
        ], $overrides);
    }

    protected function bookWithPdf(?array $loanStatus = null): Book
    {
        $book = Book::factory()->create([
            'file' => 'books/digital.pdf',
        ]);

        Storage::disk('local')->put('books/digital.pdf', '%PDF-1.4 test');

        if ($loanStatus !== null) {
            Loan::factory()->create([
                'book_id' => $book->id,
                'user_id' => User::factory()->anggota()->create()->id,
                'status' => $loanStatus,
            ]);
        }

        return $book;
    }
}
