<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingControlsTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    public function test_query_page_dipakai_untuk_membuka_halaman_tertentu(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 42]))
            ->assertOk()
            ->assertSee(route('books.file', $book).'#page=42', false);
    }

    /**
     * Task 11.9 — kontrol zoom harus ikut terkirim bersama viewport-nya.
     *
     * Yang diuji di sini hanya keberadaan kontrol (perilaku kliknya ada di
     * browser, tidak dicakup test PHP). Yang lebih penting justru di
     * SecurityTest: CSP harus `object-src 'self'`, kalau tidak `<object>`
     * PDF-nya diblokir dan halaman ini selalu menampilkan fallback.
     */
    public function test_pembaca_menampilkan_kontrol_zoom_dan_viewport(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 120);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('data-reader-viewport', false)
            ->assertSee('data-reader-zoom-controls', false)
            ->assertSee('data-reader-zoom-in', false)
            ->assertSee('data-reader-zoom-out', false)
            ->assertSee('data-reader-zoom-reset', false)
            ->assertSee(__('reader.zoom_in'))
            ->assertSee(__('reader.zoom_reset'));
    }

    /**
     * Task 11.10 — tutup/buka lagi PDF.
     *
     * Tombol "Tutup PDF" hidup di toolbar (yang tersembunyi sampai JS aktif),
     * panel "sudah ditutup" sudah dirender tersembunyi lengkap dengan templated
     * teks halamannya, dan tombol buka ulang menunggu di sana.
     */
    public function test_pembaca_menampilkan_kontrol_tutup_dan_buka_ulang(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 89);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('data-reader-close', false)
            ->assertSee('data-reader-open', false)
            ->assertSee('data-reader-closed', false)
            ->assertSee('data-page-template', false)
            ->assertSee(__('reader.close_pdf'))
            ->assertSee(__('reader.open_pdf_again'))
            ->assertSee(__('reader.pdf_closed'));
    }
}
