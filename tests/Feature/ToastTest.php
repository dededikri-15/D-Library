<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ToastTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['role' => User::ROLE_ANGGOTA]);
    }

    private function contentFor(User $user, string $route, array $session = []): string
    {
        return $this->actingAs($user)
            ->withSession($session)
            ->get($route)
            ->assertOk()
            ->getContent();
    }

    public function test_setiap_halaman_memiliki_wilayah_toast(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-toast-region', false)
            ->assertSee('aria-label="Notifikasi"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_wilayah_toast_menyediakan_template_keempat_varian(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-toast-template="success"', false)
            ->assertSee('data-toast-template="error"', false)
            ->assertSee('data-toast-template="warning"', false)
            ->assertSee('data-toast-template="info"', false);
    }

    public function test_template_toast_memuat_ikon_dan_warna_status(): void
    {
        $content = $this->get(route('books.index'))->assertOk()->getContent();

        $start = strpos($content, 'data-toast-template="success"');
        $block = substr($content, $start, 1200);

        // Warna status dan ikon harus tetap berasal dari komponen Blade, bukan
        // dari string HTML yang ditulis ulang di app.js.
        $this->assertStringContainsString('border-available/40', $block);
        $this->assertStringContainsString('data-toast-body', $block);
        $this->assertStringContainsString('data-toast-close', $block);
    }

    public function test_flash_status_dirender_sebagai_toast_melayang(): void
    {
        $content = $this->contentFor(
            $this->member(),
            route('favorites.index'),
            ['status' => 'Buku berhasil dihapus.']
        );

        $regionStart = strpos($content, 'data-toast-region');
        $this->assertNotFalse($regionStart);

        $inside = substr($content, $regionStart);
        $this->assertStringContainsString('Buku berhasil dihapus.', $inside);
        $this->assertStringContainsString('data-auto-dismiss', $inside);
    }

    public function test_galat_validasi_tetap_inline_bukan_toast(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['title' => ['Judul buku wajib diisi.']]));

        $content = $this->contentFor(
            $this->member(),
            route('favorites.index'),
            ['errors' => $errors]
        );

        $main = strpos($content, 'id="konten"');
        $region = strpos($content, 'data-toast-region');

        $this->assertNotFalse($main);
        $this->assertNotFalse($region);

        // Alert validasi harus muncul di dalam <main>, yaitu sebelum wilayah
        // toast, supaya tidak ikut hilang sendiri setelah beberapa detik.
        $this->assertStringContainsString('Judul buku wajib diisi.', substr($content, 0, $region));
        $this->assertStringNotContainsString('Periksa kembali isian Anda', substr($content, $region));
    }

    public function test_wilayah_toast_sendiri_bukan_toast(): void
    {
        $content = $this->get(route('books.index'))->assertOk()->getContent();

        $start = strpos($content, '<div data-toast-region');
        $tag = substr($content, $start, strpos($content, '>', $start) - $start);

        // Kalau wilayah ikut ber atribut `data-toast`, `trim()` akan
        // menghitungnya sebagai toast dan langsung menganggap kuota penuh.
        $this->assertStringNotContainsString('data-toast ', $tag);
        $this->assertStringNotContainsString('data-toast>', $tag);
    }

    public function test_tetap_mendapat_wilayah_toast_pada_halaman_tamu(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-toast-region', false);
    }
}
