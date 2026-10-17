<?php

namespace Tests\Concerns;

use App\Models\Book;

/**
 * Pembuat buku cepat untuk test katalog — dipakai bersama oleh kelas
 * hasil pemecahan BookCatalogTest.
 */
trait ProvidesCatalogBook
{
    private function makeBook(array $overrides = []): Book
    {
        return Book::factory()->create($overrides);
    }
}
