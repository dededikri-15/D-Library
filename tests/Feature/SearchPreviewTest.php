<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SearchPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function book(array $overrides = []): Book
    {
        return Book::factory()->create($overrides);
    }

    public function test_endpoint_pencarian_mengembalikan_json_dengan_field_yang_dibutuhkan_panel(): void
    {
        $book = $this->book(['title' => 'Dilan 1990']);

        $this->getJson(route('books.search', ['q' => 'Dilan']))
            ->assertOk()
            ->assertJsonStructure([
                'results' => [['title', 'author', 'url', 'status', 'status_label', 'is_borrowed']],
            ])
            ->assertJsonPath('results.0.title', 'Dilan 1990')
            ->assertJsonPath('results.0.url', route('books.show', $book));
    }

    public function test_satu_kata_kunci_mencakup_judul_isbn_penulis_kategori_dan_penerbit(): void
    {
        $author = Author::factory()->create(['name' => 'Andrea Hirata']);
        $category = Category::factory()->create(['name' => 'Fiksi Nusantara']);
        $publisher = Publisher::factory()->create(['name' => 'Mizan Graha']);

        // Pengisi kolom yang TIDAK ikut dicari dibuat eksplisit per buku, jadi
        // tidak mungkin ikut cocok dan membuat test ini lulus untuk alasan
        // salah. `books.category_id` NOT NULL, jadi tiap buku tetap butuh
        // kategori — kategori kabur dipakai di sini.
        $irrelevantAuthor = Author::factory()->create(['name' => 'Penulis Lain']);
        $irrelevantCategory = Category::factory()->create(['name' => 'Kategori Lain']);
        $irrelevantPublisher = Publisher::factory()->create(['name' => 'Penerbit Lain']);

        $neutral = [
            'author_id' => $irrelevantAuthor->id,
            'category_id' => $irrelevantCategory->id,
            'publisher_id' => $irrelevantPublisher->id,
        ];

        // Setiap buku sengaja dibuat cocok lewat kolom yang BERBEDA, dengan
        // judul yang sama sekali tidak mirip kata kuncinya. Kalau pencarian
        // hanya memotong judul, test ini gagal.
        $byTitle = $this->book([...$neutral, 'title' => 'Petualangan Sunan']);
        $byIsbn = $this->book([...$neutral, 'title' => 'Kode Rahasia', 'isbn' => '978-602-11-1234-5']);
        $byAuthor = $this->book([...$neutral, 'title' => 'Karya Andrea', 'author_id' => $author->id]);
        $byCategory = $this->book([...$neutral, 'title' => 'Kumpulan Cerita', 'category_id' => $category->id]);
        $byPublisher = $this->book([...$neutral, 'title' => 'Edisi Terbit', 'publisher_id' => $publisher->id]);
        $this->book([...$neutral, 'title' => 'Tidak Relevan']);

        // "Sunan" hanya ada di satu judul; ISBN penuh di buku lain.
        $this->getJson(route('books.search', ['q' => 'Sunan']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $byTitle->title);

        // ISBN sengaja diuji TANPA tanda penghubung: yang diketik orang
        // biasanya "9786021112345", sementara yang tersimpan bergaris.
        $this->getJson(route('books.search', ['q' => '9786021112345']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $byIsbn->title);

        $this->getJson(route('books.search', ['q' => 'Andrea']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $byAuthor->title)
            ->assertJsonPath('results.0.author', 'Andrea Hirata');

        $this->getJson(route('books.search', ['q' => 'Fiksi Nusantara']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $byCategory->title);

        $this->getJson(route('books.search', ['q' => 'Mizan Graha']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $byPublisher->title);
    }

    public function test_katalog_menyediakan_atribut_pratinjau_pencarian(): void
    {
        $this->book(['title' => 'Buku Alfa']);

        $content = $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-search-input', false)
            ->assertSee('data-search-results', false)
            ->assertSee(route('books.search'), false)
            ->assertSee('role="combobox"', false)
            ->getContent();

        $this->assertStringContainsString('data-search-min="2"', $content);
    }

    public function test_panel_hasil_tidak_dirender_di_html_sampai_diminta(): void
    {
        $this->book(['title' => 'Buku Alfa']);

        $content = $this->get(route('books.index'))->assertOk()->getContent();

        $start = strpos($content, '<div data-search-results');

        $this->assertNotFalse($start, 'Panel hasil pencarian harus ada di katalog.');
        $this->assertStringContainsString('hidden', substr($content, $start, 120));
        $this->assertStringNotContainsString('<li', substr($content, $start, 120));
    }

    public function test_tamu_tidak_melihat_buku_nonaktif_di_pratinjau(): void
    {
        $this->book(['title' => 'Buku Nonaktif Tersembunyi', 'status' => Book::STATUS_INACTIVE]);

        $this->getJson(route('books.search', ['q' => 'Nonaktif']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_pustakawan_tetap_melihat_buku_nonaktif_di_pratinjau(): void
    {
        $book = $this->book(['title' => 'Buku Nonaktif Terlihat', 'status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->getJson(route('books.search', ['q' => 'Nonaktif']))
            ->assertOk()
            ->assertJsonPath('results.0.title', 'Buku Nonaktif Terlihat')
            ->assertJsonPath('results.0.status', Book::STATUS_INACTIVE)
            ->assertJsonPath('results.0.is_borrowed', true);
    }

    public function test_pratinjau_menghormati_filter_kategori_dan_status(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $match = $this->book(['title' => 'BukuINDER', 'category_id' => $category->id]);
        $other = $this->book([
            'title' => 'BukuINDER Lain',
            'category_id' => $otherCategory->id,
            'status' => Book::STATUS_BORROWED,
        ]);

        $this->getJson(route('books.search', [
            'q' => 'BukuINDER',
            'category' => $category->slug,
            'status' => Book::STATUS_AVAILABLE,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', $match->title)
            ->assertJsonPath('results.0.is_borrowed', false);

        $this->assertNotSame($match->id, $other->id);
    }

    public function test_judul_dengan_tanda_kutip_berhasil_diterima(): void
    {
        $this->book(['title' => 'Buku "Kutip" Unik']);

        $this->getJson(route('books.search', ['q' => '"Kutip"']))
            ->assertOk()
            ->assertJsonCount(1, 'results');
    }

    public function test_kata_kunci_terlalu_panjang_tidak_melayani_hasil_ngawur(): void
    {
        $this->book(['title' => 'Buku Alfa']);

        // Tidak boleh 422 (endpoint JSON tidak punya form yang bisa
        // menampilkan error) dan tidak boleh 500. Yang penting: hasil KOSONG.
        // Kalau `q` yang invalid dibuang begitu saja, endpoint ini akan
        // mengembalikan "Buku Alfa" seolah-olah itu hasil pencarian.
        $this->getJson(route('books.search', ['q' => str_repeat('a', 200)]))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_hasil_terbatas_udel_dan_berurutan(): void
    {
        foreach (range(1, 12) as $index) {
            $this->book(['title' => sprintf('Kata Kunci Buku %02d', $index)]);
        }

        $this->book(['title' => 'Tidak Termasuk Kata']);

        $response = $this->getJson(route('books.search', ['q' => 'Kata Kunci']))->assertOk();

        $this->assertCount(8, $response->json('results'));
        $this->assertSame('Kata Kunci Buku 01', $response->json('results.0.title'));
        $this->assertSame('Kata Kunci Buku 08', $response->json('results.7.title'));
    }

    public function test_nama_route_pencarian_tidak_tertimpa_route_detail(): void
    {
        $this->assertSame('http://localhost/buku/pencarian', route('books.search'));
        $this->assertNotSame(route('books.search'), route('books.index'));
        $this->assertTrue(Route::has('books.search'));
        $this->assertTrue(Route::has('books.show'));
    }

    public function test_endpoint_pencarian_dibatasi_rate_limit(): void
    {
        // Angka batas dibaca dari config, bukan ditulis ulang di sini, supaya
        // test ini tidak berbohong kalau nilainya diubah di config/perpustakaan.
        // `config()` di dalam test tidak berpengaruh: limiter sudah didaftarkan
        // saat AppServiceProvider::boot() membaca nilainya.
        $limit = (int) config('perpustakaan.security.search_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->getJson(route('books.search', ['q' => "satu $i"]))->assertOk();
        }

        $this->getJson(route('books.search', ['q' => 'kelebihan']))
            ->assertStatus(429);
    }

    public function test_judul_buku_di_panel_pratinjau_tidak_dis_markup(): void
    {
        $this->book(['title' => '<script>alert(1)</script>']);

        $response = $this->getJson(route('books.search', ['q' => 'alert']));

        $response->assertOk()
            ->assertJsonPath('results.0.title', '<script>alert(1)</script>');

        // Panel di browser aman karena JS memakai textContent. Tapi respons
        // JSON-nya sendiri juga tidak boleh memuat tag mentah, supaya tidak
        // ada penyusun kode yang menyalin `data.url` ke innerHTML nanti dan
        // menyangka respons ini sudah aman.
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }
}
