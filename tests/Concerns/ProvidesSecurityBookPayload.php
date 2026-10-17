<?php

namespace Tests\Concerns;

use App\Models\Book;

/**
 * Payload POST/PUT buku untuk test keamanan — dipakai bersama oleh kelas
 * hasil pemecahan SecurityTest yang menguji write route (tak ada di kelas
 * lain, karena default judul/ISBN-nya khusus uji keamanan).
 */
trait ProvidesSecurityBookPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function bookPayload(array $overrides = []): array
    {
        $book = Book::factory()->make();

        return array_merge([
            'title' => 'Buku Uji Keamanan',
            'isbn' => '978-602-000-000-1',
            'publication_year' => 2024,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
            'status' => Book::STATUS_AVAILABLE,
        ], $overrides);
    }
}
