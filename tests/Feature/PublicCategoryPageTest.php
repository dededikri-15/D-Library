<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_bisa_membuka_halaman_kategori_publik(): void
    {
        $this->get(route('categories.public'))->assertOk();
    }

    public function test_halaman_kategori_menampilkan_nama_dan_jumlah_buku(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        Book::factory()->count(2)->for($category)->create();

        $this->get(route('categories.public'))
            ->assertOk()
            ->assertSee('Fiksi')
            ->assertSee('2 buku');
    }

    public function test_kategori_tanpa_buku_tidak_ditampilkan(): void
    {
        Category::factory()->create(['name' => 'Kategori Kosong']);

        $this->get(route('categories.public'))
            ->assertOk()
            ->assertDontSee('Kategori Kosong');
    }

    public function test_buku_tidak_aktif_tidak_ikut_di_hitungan(): void
    {
        $category = Category::factory()->create(['name' => 'Koleksi Lama']);
        Book::factory()->for($category)->create(['status' => Book::STATUS_AVAILABLE]);
        Book::factory()->for($category)->inactive()->create();

        $this->get(route('categories.public'))
            ->assertOk()
            ->assertSee('1 buku')
            ->assertDontSee('2 buku');
    }

    public function test_klik_kategori_mengarah_ke_katalog_yang_disaring(): void
    {
        $category = Category::factory()->create(['slug' => 'fiksi']);
        Book::factory()->for($category)->create();

        $this->get(route('categories.public'))
            ->assertOk()
            ->assertSee(route('books.index', ['category' => 'fiksi']), escape: false);
    }

    public function test_halaman_kategori_publik_tidak_bentrok_dengan_modul_staff(): void
    {
        // Dua route dengan URI sama akan menimpa nama route-nya, sehingga
        // `categories.index` (dipakai redirect setelah simpan) hilang.
        $this->assertNotSame(
            route('categories.public'),
            route('categories.index'),
            'URI halaman kategori publik harus berbeda dari URI CRUD kategori staff.',
        );
    }
}
