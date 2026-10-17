<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesCatalogBook;
use Tests\TestCase;

class CatalogSearchFilterTest extends TestCase
{
    use ProvidesCatalogBook;
    use RefreshDatabase;

    public function test_catalog_can_search_by_isbn_with_or_without_dashes(): void
    {
        $this->makeBook(['title' => 'Buku Alfa', 'isbn' => '978-602-111-222-3']);
        $this->makeBook(['title' => 'Buku Beta', 'isbn' => '978-602-999-888-7']);

        // ISBN tersimpan bergaris, tapi yang diketik orang sering tidak.
        $this->get(route('books.index', ['q' => '978-602-111-222-3']))
            ->assertOk()
            ->assertSee('Buku Alfa')
            ->assertDontSee('Buku Beta');

        $this->get(route('books.index', ['q' => '9786021112223']))
            ->assertOk()
            ->assertSee('Buku Alfa')
            ->assertDontSee('Buku Beta');
    }

    public function test_catalog_can_search_by_author_name(): void
    {
        $andi = Author::factory()->create(['name' => 'Andi Prasetyo']);
        $budi = Author::factory()->create(['name' => 'Budi Santoso']);

        // Judul sengaja tidak memuat nama penulis: yang dicari hanya relasi.
        $this->makeBook(['title' => 'Kumpulan Cerita', 'author_id' => $andi->id]);
        $this->makeBook(['title' => 'Catatan harian', 'author_id' => $budi->id]);

        $this->get(route('books.index', ['q' => 'Andi Prasetyo']))
            ->assertOk()
            ->assertSee('Kumpulan Cerita')
            ->assertDontSee('Catatan harian');
    }

    public function test_catalog_can_filter_by_publisher(): void
    {
        $a = Publisher::factory()->create(['name' => 'Penerbit Alpha']);
        $b = Publisher::factory()->create(['name' => 'Penerbit Beta']);

        $this->makeBook(['title' => 'Buku Satu', 'publisher_id' => $a->id]);
        $this->makeBook(['title' => 'Buku Dua', 'publisher_id' => $b->id]);

        $this->get(route('books.index', ['publisher' => 'Penerbit Alpha']))
            ->assertOk()
            ->assertSee('Buku Satu')
            ->assertDontSee('Buku Dua');
    }

    public function test_catalog_can_filter_by_status(): void
    {
        $this->makeBook(['title' => 'Buku Tersedia', 'status' => Book::STATUS_AVAILABLE]);
        $this->makeBook(['title' => 'Buku Dipinjam', 'status' => Book::STATUS_BORROWED]);

        $this->get(route('books.index', ['status' => 'available']))
            ->assertOk()
            ->assertSee('Buku Tersedia')
            ->assertDontSee('Buku Dipinjam');
    }

    public function test_inactive_books_never_appear_in_public_catalog(): void
    {
        $this->makeBook(['title' => 'Buku Aktif']);
        $this->makeBook(['title' => 'Buku Nonaktif', 'status' => Book::STATUS_INACTIVE]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Aktif')
            ->assertDontSee('Buku Nonaktif');

        // Dicoba juga lewat URL: `?status=inactive` tidak boleh membocorkan
        // buku nonaktif ke katalog publik.
        $this->get(route('books.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertDontSee('Buku Nonaktif');
    }
}
