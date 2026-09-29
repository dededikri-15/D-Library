<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Loading indicator (Task 14.8).
 *
 * Yang diuji di sini BUKAN "spinner-nya berputar" — itu urusan browser dan
 * mustahil dibuktikan dari PHPUnit. Yang diuji adalah KONTRAK antara Blade dan
 * JavaScript: markup yang dibutuhkan JS sudah ada di HTML, dan markup itu
 * punya sifat yang membuatnya benar sebagai loading state.
 *
 * Kalau kontrak ini dilanggar, gejalanya: panel pencarian diam selama 300 ms
 * selama 300 ms + waktu jaringan tanpa ada apa pun yang berubah, lalu hasilnya
 * tiba-tiba muncul. Tidak ada error, tidak ada test gagal — user cuma mengira
 * kotak pencariannya rusak.
 */
class LoadingIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_pencarian_menyediakan_template_skeleton(): void
    {
        $content = $this->get(route('books.index'))
            ->assertOk()
            ->getContent();

        // JS mengklon `<template>` ini. Kalau atributnya hilang atau diganti
        // namanya, `cloneSkeleton()` diam-diam mengembalikan `null` dan panel
        // tidak pernah menampilkan apa-apa saat request berjalan.
        $this->assertStringContainsString('data-search-skeleton', $content);
    }

    public function test_skeleton_memiliki_keterangan_yang_bisa_dilihat(): void
    {
        $content = $this->get(route('books.index'))
            ->assertOk()
            ->getContent();

        // Caption harus TEKS yang terlihat, bukan `sr-only`. Alasannya ada di
        // `.skeleton-caption` (resources/css/app.css): aturan global
        // `prefers-reduced-motion` mematikan `animate-pulse`, jadi skeleton
        // yang hanya berdenyut akan berubah menjadi blok abu-abu diam. Kalimat
        // yang menyatakan keadaannya harus tetap ada dengan atau tanpa animasi.
        $this->assertStringContainsString('skeleton-caption', $content);
        $this->assertStringNotContainsString('sr-only', $this->skeletonMarkup());
    }

    public function test_skeleton_menyatakan_status_ke_teknologi_bantu(): void
    {
        $markup = $this->skeletonMarkup();

        // `role="status"` + `aria-live="polite"` membuat perubahan isi
        // diumumkan tanpa memutus apa yang sedang dibaca user. Tanpa itu,
        // skeleton hanya benda visual.
        $this->assertStringContainsString('role="status"', $markup);
        $this->assertStringContainsString('aria-live="polite"', $markup);

        // Baris abu-abu dekoratif: screen reader tidak perlu membacakan
        // "baris kosong" sebanyak lima kali.
        $this->assertStringContainsString('aria-hidden="true"', $markup);
    }

    public function test_skeleton_memakai_class_bersama_bukan_utility_inline(): void
    {
        $markup = $this->skeletonMarkup();

        // Bentuk visual (tinggi, radius, warna) hanya boleh datang dari
        // `.skeleton-*` di resources/css/app.css. Kalau utility inline
        // (`h-3 w-full rounded bg-secondary/10`) ditulis di markup, ada dua
        // definisi tampilan yang bisa berbeda, dan mengubah CSS tidak akan
        // mengubah markup.
        foreach (['skeleton-cover', 'skeleton-lines', 'skeleton-line', 'skeleton-spinner'] as $class) {
            $this->assertStringContainsString($class, $markup, "Class {$class} tidak dipakai.");
        }

        $this->assertStringNotContainsString('bg-secondary/10', $markup);
    }

    public function test_jumlah_baris_skeleton_bisa_diatur(): void
    {
        $markup = $this->renderSkeleton(5);

        $this->assertSame(5, substr_count($markup, 'skeleton-cover'));
    }

    public function test_komponen_tidak_menempel_role_di_template(): void
    {
        // Isi `<template>` tidak pernah tampil dan tidak boleh dihitung screen
        // reader. Kalau `role="status"` ikut menempel di elemen pembungkusnya,
        // setiap kali panel memuat ulang, teknologi bantu mengumumkan
        // "Memuat data" dari dalam template yang tidak sedang terlihat.
        $markup = $this->renderSkeleton(null, 'template');

        $this->assertStringStartsWith('<template', trim($markup));
        $this->assertStringNotContainsString('<template role=', $markup);
    }

    public function test_skeleton_tidak_muncul_di_halaman_yang_tidak_memuat_data(): void
    {
        $book = Book::factory()->create();

        // Halaman detail buku tidak punya fetch yang mengisi daftar, jadi
        // skeleton hanya akan menambah bobot HTML tanpa pernah tampil.
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('data-search-skeleton', false);
    }

    /**
     * Render komponennya langsung, terpisah dari halaman katalog, supaya
     * kegagalan di sini menunjuk ke komponen dan bukan ke panel pencarian.
     */
    private function skeletonMarkup(): string
    {
        return $this->renderSkeleton();
    }

    /**
     * Render `<x-loading-skeleton>` dengan parameter yang bisa diubah-ubah.
     *
     * `TestView` tidak punya `render()`; `__toString()` yang memanggil
     * viewFactory->make(), sama seperti yang dipakai assertion di atasnya.
     */
    private function renderSkeleton(?int $rows = null, ?string $as = null): string
    {
        $attributes = '';

        if ($rows !== null) {
            $attributes .= ' :rows="'.$rows.'"';
        }

        if ($as !== null) {
            $attributes .= ' as="'.$as.'"';
        }

        return (string) $this->blade('<x-loading-skeleton'.$attributes.' />');
    }
}
