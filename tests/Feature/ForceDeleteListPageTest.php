<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ForceDeleteListPageTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    /**
     * Tombol hapus tidak boleh pernah dimatikan. Menahannya hanya karena
     * masih ada buku membuat pustakawan tidak bisa membereskan data yang
     * salah input tanpa membuka tiap buku satu per satu.
     */
    public function test_tombol_hapus_tidak_pernah_dimatikan(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $edit = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus kategori</',
            $edit,
        );

        $index = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus</',
            $index,
        );
    }

    /**
     * Di halaman daftar, Hapus berdiri sendiri di kolomnya sendiri — terpisah
     * dari Edit. Kalau masih digabung di satu kolom "Aksi", dua tombol
     * berdampingan itu mudah salah klik, dan yang salah klik biasanya Hapus.
     */
    public function test_daftar_mempunyai_kolom_hapus_tersendiri(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<th[^>]*>Hapus<\/th>/', $content);
        $this->assertMatchesRegularExpression('/<th[^>]*>Edit<\/th>/', $content);
        $this->assertStringContainsString(
            route('categories.destroy', $category),
            $content,
            'Form hapus di halaman daftar harus mengarah ke route hapus.',
        );
    }

    /**
     * Di tabel daftar, akibatnya harus terbaca tanpa perlu menekan apa pun:
     * jumlah buku yang ikut hilang ditulis di baris itu sendiri, dan
     * kalimat konfirmasinya menyebut hal yang sama.
     */
    public function test_daftar_menuliskan_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('2 buku ikut terhapus', $content);
        $this->assertStringContainsString('2 buku ikut terhapus permanen, beserta file dan riwayat peminjamannya.', $content);

        // Tanda kutip harus tampil sebagai karakter, bukan `&quot;`. Nilai
        // `data-confirm` di-escape Blade ke `&quot;` di HTML, dan browser
        // membacanya kembali jadi `"` — kalau `&quot;` juga ditulis manual
        // di dalam Blade, teks itu yang muncul di dialog.
        $this->assertStringNotContainsString('&quot;', html_entity_decode($content, ENT_QUOTES));
    }
}
