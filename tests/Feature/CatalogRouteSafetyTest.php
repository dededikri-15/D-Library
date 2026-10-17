<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogRouteSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_book_returns_404(): void
    {
        $this->get('/buku/999999')->assertNotFound();
    }

    /**
     * Regression test: dulu /buku/abc menghasilkan HTTP 500.
     *
     * Di SQLite `where id = 'abc'` menghasilkan null (aman), tapi di PostgreSQL
     * kolom bigint menolak teks dan melempar QueryException. Karena test selalu
     * jalan di SQLite, masalah ini tidak akan terdeteksi tanpa pembatasan
     * Route::pattern di AppServiceProvider.
     */
    public function test_non_numeric_book_id_returns_404_not_server_error(): void
    {
        $this->get('/buku/abc')->assertNotFound();
        $this->get('/buku/1abc')->assertNotFound();
        $this->get('/buku/0')->assertNotFound();
    }

    public function test_non_numeric_ids_are_rejected_on_master_data_routes_too(): void
    {
        $this->get('/kategori/xyz')->assertNotFound();
        $this->get('/penulis/abc')->assertNotFound();
        $this->get('/penerbit/abc')->assertNotFound();
    }

    public function test_non_numeric_book_id_returns_404_not_500(): void
    {
        // SQLite menerima teks di kolom integer, PostgreSQL menolak dengan
        // error. Route sudah diberi whereNumber, jadi harus 404 di keduanya.
        $this->get('/buku/abc')->assertNotFound();
        $this->get('/buku/1abc')->assertNotFound();
        $this->get('/buku/-1')->assertNotFound();
        $this->get('/buku/1.5')->assertNotFound();
    }

    public function test_catalog_is_paginated(): void
    {
        Book::factory()->count(15)->create();

        $response = $this->get(route('books.index'))->assertOk();

        $perPage = config('perpustakaan.pagination.per_page');
        $this->assertCount($perPage, $response->viewData('books')->items());
        $this->assertSame(15, $response->viewData('books')->total());
    }
}
