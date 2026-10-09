<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menu sidebar untuk pustakawan.
 *
 * Yang diuji di sini bukan "`/kategori` bisa diakses" — halamannya memang
 * sudah ada dan sudah dilindungi middleware. Yang diuji adalah apakah
 * pustakawan punya jalan menuju halaman itu dari menu.
 *
 * Kenapa ini penting: sebelum menu ini ada, halaman `/kategori`, `/penulis`,
 * dan `/penerbit` benar-benar yatim. Satu-satunya tautan menuju mereka adalah
 * tombol "Batal" di halaman create/edit miliknya sendiri, jadi satu-satunya
 * cara masuk adalah mengetik URL. Akibatnya staff bisa menambah kategori lewat
 * form buku, tapi tidak bisa menghapus kategori yang tidak sengaja ditambahkan
 * tanpa mengingat alamat URL-nya.
 */
class StaffMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function masterDataRoutes(): array
    {
        return [
            'kategori' => ['categories.index'],
            'penulis' => ['authors.index'],
            'penerbit' => ['publishers.index'],
        ];
    }

    #[DataProvider('masterDataRoutes')]
    public function test_pustakawan_melihat_tautan_kelola_master_data(string $routeName): void
    {
        $content = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.route($routeName).'"',
            $content,
            "Menu sidebar tidak punya tautan ke {$routeName}.",
        );
    }

    public function test_judul_kelompok_kelola_data_muncul(): void
    {
        $content = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Kelola Data', $content);
    }

    /**
     * Menu "Kelola Data" hanya untuk staff. Kalau bocor ke anggota, anggota
     * akan melihat tombol yang selalu menolak permintaannya.
     */
    public function test_anggota_tidak_melihat_menu_kelola_master_data(): void
    {
        $content = $this->actingAs(User::factory()->create())
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Kelola Data', $content);
        $this->assertStringNotContainsString('href="'.route('categories.index').'"', $content);
        $this->assertStringNotContainsString('href="'.route('authors.index').'"', $content);
        $this->assertStringNotContainsString('href="'.route('publishers.index').'"', $content);
    }

    /**
     * Ada dua menu bernama "Kategori": satu halaman publik (`categories.public`)
     * dan satu halaman kelola (`categories.index`). Kalau pola aktifnya tumpang
     * tindih, keduanya menyala bersamaan saat salah satu dibuka.
     */
    public function test_hanya_satu_menu_kategori_yang_menyala(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $onPublic = $this->actingAs($librarian)->get(route('categories.public'))->getContent();
        $onManage = $this->actingAs($librarian)->get(route('categories.index'))->getContent();

        // Di halaman publik, yang menyala harus menunya yang publik saja.
        $this->assertSame(1, $this->countActiveLinks($onPublic, route('categories.public')));
        $this->assertSame(0, $this->countActiveLinks($onPublic, route('categories.index')));

        // Di halaman kelola, sebaliknya.
        $this->assertSame(1, $this->countActiveLinks($onManage, route('categories.index')));
        $this->assertSame(0, $this->countActiveLinks($onManage, route('categories.public')));
    }

    /**
     * Menu "Katalog" dan "Kelola Buku" dulu sama-sama menunjuk `/buku` dan
     * sama-sama memakai pola `books.*`, jadi keduanya menyala bersamaan
     * entah pengguna masuk dari menu yang mana. Sekarang URL-nya dipisah:
     * katalog milik staf memakai `?lihat=katalog`, kelola memakai `/buku`.
     *
     * Diuji dengan menghitung link aktif per URL — bukan per label — karena
     * keduanya tetap memakai nama route `books.index`.
     */
    public function test_hanya_satu_menu_buku_yang_menyala(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $onManage = $this->actingAs($librarian)->get(route('books.index'))->getContent();
        $onCatalog = $this->actingAs($librarian)
            ->get(route('books.index', ['lihat' => 'katalog']))
            ->getContent();

        $manageUrl = route('books.index');
        $catalogUrl = route('books.index', ['lihat' => 'katalog']);

        // Di halaman kelola, hanya "Kelola Buku" yang menyala.
        $this->assertSame(1, $this->countActiveLinks($onManage, $manageUrl));
        $this->assertSame(0, $this->countActiveLinks($onManage, $catalogUrl));

        // Di halaman katalog, sebaliknya.
        $this->assertSame(1, $this->countActiveLinks($onCatalog, $catalogUrl));
        $this->assertSame(0, $this->countActiveLinks($onCatalog, $manageUrl));
    }

    /**
     * Halaman detail, create, dan edit juga harus menyala tepat satu menu.
     */
    public function test_halaman_form_buku_hanya_menyala_menu_kelola(): void
    {
        $book = Book::factory()->create();
        $librarian = User::factory()->pustakawan()->create();

        $create = $this->actingAs($librarian)->get(route('books.create'))->getContent();
        $edit = $this->actingAs($librarian)->get(route('books.edit', $book))->getContent();

        foreach ([$create, $edit] as $html) {
            // Yang menyala harus menu kelola (href `/buku`), bukan katalog.
            $this->assertSame(1, $this->countActiveLinks($html, route('books.index')));
            $this->assertSame(0, $this->countActiveLinks($html, route('books.index', ['lihat' => 'katalog'])));
        }

        $detail = $this->actingAs($librarian)->get(route('books.show', $book))->getContent();
        $this->assertSame(1, $this->countActiveLinks($detail, route('books.index', ['lihat' => 'katalog'])));
        $this->assertSame(0, $this->countActiveLinks($detail, route('books.index')));
    }

    /**
     * Hitung berapa link menu menuju `$url` yang sedang aktif.
     *
     * Pencocokan dibatasi di dalam `<nav aria-label="Menu">` supaya tautan
     * yang sama di footer atau di isi halaman tidak ikut terhitung.
     */
    private function countActiveLinks(string $html, string $url): int
    {
        preg_match('/<nav\b[^>]*aria-label="Menu".*?<\/nav>/si', $html, $nav);

        $this->assertNotEmpty($nav, 'Sidebar tidak memiliki blok <nav aria-label="Menu">.');

        preg_match_all(
            '/<a\b[^>]*href="'.preg_quote($url, '/').'"[^>]*>/i',
            $nav[0],
            $matches,
        );

        $active = 0;

        foreach ($matches[0] as $tag) {
            if (str_contains($tag, 'sidebar-link-active')) {
                $active++;
            }
        }

        return $active;
    }
}
