<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;

class FavoriteFormMarkupTest extends TestCase
{
    use RefreshDatabase;
    use CreatesLibraryUsers;

    /*
     * Bentuk HTML yang dikirim JS. Test ini yang menangkap "AJAX-nya jalan
     * tapi formnya salah", yang tidak akan terlihat dari respons JSON.
     */

    public function test_form_favorit_menyediakan_semua_data_yang_dibutuhkan_js(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-ajax', $content);
        $this->assertStringContainsString('data-favorite-toggle', $content);
        // Tombol dalam form toggle memakai atribut generik (`data-toggle-*`)
        // supaya `applyToggleResult()` bisa dipakai ulang oleh form toggle
        // lain tanpa mengubah JS-nya.
        $this->assertStringContainsString('data-toggle-button', $content);
        $this->assertStringContainsString('data-toggle-label', $content);
        $this->assertStringContainsString('data-toggle-icon', $content);

        // Kedua endpoint harus ada di DOM, karena satu form dipakai untuk
        // tambah DAN hapus. Kalau `data-destroy-url` hilang, klik kedua
        // mengirim POST ke endpoint tambah — jadi buku tidak bisa dihapus
        // tanpa reload halaman.
        $this->assertStringContainsString('data-store-url="'.route('favorites.store', $book).'"', $content);
        $this->assertStringContainsString('data-destroy-url="'.route('favorites.destroy', $book).'"', $content);

        // Label dua arah juga harus ada di Blade, bukan di-hardcode di JS.
        $this->assertStringContainsString('data-label-add="Tambah ke favorit"', $content);
        $this->assertStringContainsString('data-label-remove="Hapus dari favorit"', $content);
    }

    public function test_form_favorit_yang_sudah_terisi_menunjuk_ke_endpoint_hapus(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="true"', $content);
        $this->assertStringContainsString('name="_method" value="DELETE"', $content);
    }

    public function test_form_favorit_belum_terisi_menunjuk_ke_endpoint_tambah(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="false"', $content);
        $this->assertStringNotContainsString('name="_method" value="DELETE"', $content);
    }
}
