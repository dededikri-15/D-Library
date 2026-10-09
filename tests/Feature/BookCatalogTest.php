<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(array $overrides = []): Book
    {
        return Book::factory()->create($overrides);
    }

    /**
     * Menu "Katalog" staf membuka `/buku?lihat=katalog`: tampilan kartu
     * seperti katalog publik, bukan halaman kelola. Buku nonaktif tetap
     * disembunyikan karena mode ini bukan mode pengelolaan.
     *
     * Yang dibedakan bukan judul halama (keduanya memakai judul "Katalog
     * Buku" di tag <title>), tapi deskripsi mode dan aksi staf.
     */
    public function test_staff_can_browse_the_public_catalog_view(): void
    {
        $this->makeBook(['title' => 'Buku Katalog Staf']);
        $this->makeBook(['title' => 'Buku Nonaktif Staf', 'status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.index', ['lihat' => 'katalog']))
            ->assertOk()
            ->assertSee(__('catalog.description'))
            ->assertSee(__('catalog.collection_eyebrow'))
            ->assertSee('Buku Katalog Staf')
            ->assertDontSee(__('catalog.management_description'))
            ->assertDontSee(__('catalog.management_eyebrow'))
            ->assertDontSee('Buku Nonaktif Staf');
    }

    public function test_public_can_view_catalog(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Alfa');
    }

    public function test_staff_can_manage_existing_books_and_edit_the_cover(): void
    {
        $book = $this->makeBook(['title' => 'Buku untuk Diedit']);
        $inactiveBook = $this->makeBook([
            'title' => 'Buku Nonaktif untuk Diedit',
            'status' => Book::STATUS_INACTIVE,
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Kelola Buku')
            ->assertSee('Tambah buku')
            ->assertSee($book->title)
            ->assertSee($inactiveBook->title)
            ->assertSee(route('books.edit', $book), false);
    }

    public function test_member_catalog_does_not_show_book_management_actions(): void
    {
        $book = $this->makeBook(['title' => 'Buku Katalog Anggota']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Katalog Buku')
            ->assertDontSee('Kelola Buku')
            ->assertDontSee(route('books.edit', $book), false);
    }

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

    public function test_filters_combine_and_pagination_links_keep_the_query_string(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $author = Author::factory()->create(['name' => 'Andi Prasetyo']);

        Book::factory()->count(15)->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);
        $this->makeBook(['title' => 'Buku Lain']);

        $response = $this->get(route('books.index', ['category' => 'fiksi', 'author' => 'Andi Prasetyo']))
            ->assertOk();

        // Dua filter harus menyempitkan hasil secara bersamaan (AND), bukan
        // salah satu saja.
        $this->assertSame(15, $response->viewData('books')->total());
        $this->assertStringNotContainsString('Buku Lain', $response->getContent());

        // Halaman berikutnya harus membawa filter yang sama. Kalau filter hilang
        // dari URL pagination, klik "2" akan mengembalikan seluruh katalog
        // tanpa filter sama sekali.
        $nextUrl = $response->viewData('books')->nextPageUrl();
        parse_str((string) parse_url($nextUrl, PHP_URL_QUERY), $nextQuery);
        $this->assertSame('fiksi', $nextQuery['category'] ?? null);
        $this->assertSame('Andi Prasetyo', $nextQuery['author'] ?? null);
        $this->assertSame('2', $nextQuery['page'] ?? null);

        $next = $this->get($nextUrl)->assertOk();
        $this->assertSame(15, $next->viewData('books')->total());
    }

    public function test_catalog_can_search_by_title(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);
        $this->makeBook(['title' => 'Buku Beta']);

        $this->get(route('books.index', ['q' => 'Alfa']))
            ->assertOk()
            ->assertSee('Buku Alfa')
            ->assertDontSee('Buku Beta');
    }

    public function test_catalog_can_filter_by_category(): void
    {
        $fiksi = Category::factory()->create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $sains = Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->makeBook(['title' => 'Novel Alpha', 'category_id' => $fiksi->id]);
        $this->makeBook(['title' => 'Buku Beta', 'category_id' => $sains->id]);

        $this->get(route('books.index', ['category' => 'fiksi']))
            ->assertOk()
            ->assertSee('Novel Alpha')
            ->assertDontSee('Buku Beta');
    }

    public function test_catalog_can_filter_by_author(): void
    {
        $andi = Author::factory()->create(['name' => 'Andi Prasetyo']);
        $budi = Author::factory()->create(['name' => 'Budi Santoso']);

        $this->makeBook(['title' => 'Buku Andi', 'author_id' => $andi->id]);
        $this->makeBook(['title' => 'Buku Budi', 'author_id' => $budi->id]);

        $this->get(route('books.index', ['author' => 'Andi Prasetyo']))
            ->assertOk()
            ->assertSee('Buku Andi')
            ->assertDontSee('Buku Budi');
    }

    public function test_catalog_can_sort_by_title(): void
    {
        $this->makeBook(['title' => 'Zulu Buku']);
        $this->makeBook(['title' => 'Alfa Buku']);

        $response = $this->get(route('books.index', ['sort' => 'title']))->assertOk();

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'Zulu Buku'),
            strpos($html, 'Alfa Buku'),
            'Buku A-Z seharusnya tampil sebelum buku Z'
        );
    }

    public function test_unknown_sort_key_falls_back_to_default_instead_of_erroring(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        // Nilai sort dari user tidak boleh bisa menyuntikkan kolom/kolom acak.
        $this->get(route('books.index', ['sort' => 'password']))->assertOk();
        $this->get(route('books.index', ['sort' => "title'; drop table books;--"]))->assertOk();
    }

    public function test_catalog_shows_empty_state_when_no_match(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        $this->get(route('books.index', ['q' => 'tidak-ada-hasil']))
            ->assertOk()
            ->assertSee('Buku tidak ditemukan');
    }

    public function test_public_can_view_book_detail(): void
    {
        $book = $this->makeBook(['title' => 'Buku Detail', 'description' => 'Sinopsis singkat.']);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Buku Detail')
            ->assertSee('Sinopsis singkat.')
            ->assertSee($book->isbn);
    }

    public function test_staff_can_open_book_edit_from_detail_page(): void
    {
        $book = $this->makeBook(['title' => 'Buku dengan Aksi Edit']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Edit buku')
            ->assertSee(route('books.edit', $book), false);
    }

    public function test_member_does_not_see_staff_book_edit_action(): void
    {
        $book = $this->makeBook(['title' => 'Buku milik katalog']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('Edit buku')
            ->assertDontSee(route('books.edit', $book), false);
    }

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

    public function test_book_card_shows_required_fields(): void
    {
        $category = Category::factory()->create(['name' => 'Teknologi']);
        $author = Author::factory()->create(['name' => 'Andi Prasetyo']);
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Digital']);

        // PRD §4: card memuat cover, judul, penulis, kategori, tahun terbit.
        $this->makeBook([
            'title' => 'Buku Lengkap',
            'publication_year' => 2024,
            'category_id' => $category->id,
            'author_id' => $author->id,
            'publisher_id' => $publisher->id,
        ]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Lengkap')
            ->assertSee('Teknologi')
            ->assertSee('Andi Prasetyo')
            ->assertSee('2024');
    }

    public function test_book_detail_shows_publisher_isbn_and_pages(): void
    {
        $book = $this->makeBook(['pages' => 320]);

        // PRD §5: detail memuat penerbit, ISBN, jumlah halaman, dan status.
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee($book->publisher->name)
            ->assertSee($book->isbn)
            ->assertSee('320');
    }
}
