<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Responsive mobile (Task 14.9).
 *
 * Yang diuji di sini adalah KONTRAK antara Blade, CSS, dan JavaScript — sama
 * seperti LoadingIndicatorTest (14.8) dan ModalTest (14.3). "Tombolnya
 * kelihatan bagus di 360px" mustahil dibuktikan dari PHPUnit; yang bisa
 * dibuktikan adalah bahwa tiga hal yang membuat problem itu terjadi tidak ada
 * lagi: view pagination yang disembunyikan di mobile, drawer yang tidak punya
 * jalan keluar tanpa keyboard, dan class utility yang hilang karena ditulis
 * di PHP.
 *
 * Semua test di sini sengaja memeriksa KONTRAK, bukan tampilan. Kalau gagal,
 * gejalanya di browser nyata biasanya "kelihatan benar" juga, cuma
 * kesalahannya tidak terasa — itu yang membuatnya mahal.
 */
class ResponsiveMobileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Katalog dengan cukup banyak buku supaya paginator benar-benar punya
     * lebih dari satu halaman. 15 per halaman, jadi 34 buku = 3 halaman.
     */
    private function seedCatalogWithPages(): void
    {
        // 15 buku per halaman (default paginator), jadi 34 buku = 3 halaman.
        // Cukup untuk memastikan daftar nomor halaman benar-benar dirender dan
        // bukan cuma satu tombol prev/next.
        Book::factory()->count(34)->for(Category::factory())->create();
    }

    /**
     * Isi blok `<nav>` pagination saja, bukan seluruh halaman.
     *
     * Dipisah supaya assertion benar-benar bicara soal pagination. Kalau
     * dicek di seluruh HTML, `hidden sm:` milik komponen lain di halaman itu
     * akan membuat test gagal walaupun pagination-nya sudah benar.
     */
    private function paginationMarkup(string $html): string
    {
        $matched = preg_match('#<nav role="navigation".*?</nav>#s', $html, $matches);

        $this->assertSame(
            1,
            $matched,
            'Blok pagination tidak ditemukan. Kalau view override hilang, ini '
            .'pertanda seluruh UI pagination hilang juga, bukan cuma warnanya.',
        );

        return $matches[0];
    }

    /**
     * Nama class yang sengaja TIDAK ditulis utuh sebagai literal.
     *
     * File di `tests/` ikut dipindai Tailwind sebagai sumber class. Jadi
     * nama utility stok yang ditulis utuh DI SINI — bahkan di dalam komentar
     * ini — akan tetap di-generate ke CSS walaupun tidak ada satu pun view
     * yang memakainya. Kalau tidak diligent, test ini justru memunculkan dead
     * CSS yang sedang ia coba buktikan tidak ada.
     *
     * Nama-nama yang diuji dirakit dari dua potongan supaya pemindainya tidak
     * pernah melihat nama lengkapnya. Jangan disederhanakan kembali, termasuk
     * di komentar.
     */
    private function stockUtility(string $prefix, string $suffix): string
    {
        return $prefix.$suffix;
    }

    public function test_nomor_halaman_tetap_tampil_di_mobile(): void
    {
        $this->seedCatalogWithPages();

        $pagination = $this->paginationMarkup(
            $this->get(route('books.index'))->assertOk()->getContent(),
        );

        // View pagination bawaan Laravel menyembunyikan daftar nomor halaman
        // di bawah 640px (`hidden sm:flex`) dan hanya menyisakan tombol
        // Sebelumnya/Berikutnya. Di katalog dengan puluhan halaman, pengguna
        // HP tidak punya cara lain tahu dia sedang di halaman berapa.
        //
        // Yang dicek di dalam blok nav: nomor halaman tidak boleh punya
        // `hidden` yang aktif di mobile sama sekali.
        $this->assertStringNotContainsString(
            $this->stockUtility('hidden sm:', 'flex'),
            $pagination,
            'View pagination bawaan masih dipakai. Nomor halaman disembunyikan '
            .'di mobile, dan pengguna HP tidak punya cara lain tahu posisinya.',
        );

        // Ringkasan "Menampilkan 1-15 dari 34 data" harus ada, karena itu
        // satu-satunya penanda posisi yang tidak butuh nomor halaman.
        $this->assertStringContainsString('pagination-summary', $pagination);
        $this->assertStringContainsString('dari', $pagination);
    }

    public function test_pagination_menggunakan_class_komponen_bukan_utility_stok(): void
    {
        $this->seedCatalogWithPages();

        $pagination = $this->paginationMarkup(
            $this->get(route('books.index'))->assertOk()->getContent(),
        );

        $this->assertStringContainsString('pagination-list', $pagination);
        $this->assertStringContainsString('pagination-item', $pagination);

        // Warna stok bawaan Laravel (abu-abu/biru) tidak cocok dengan palet
        // Slate/Indigo. Kalau utility ini muncul di markup, berarti view
        // override somehow tidak terpakai.
        $stock = [
            $this->stockUtility('border-gray', '-300'),
            $this->stockUtility('focus:ring-blue', '-300'),
            $this->stockUtility('bg-gray', '-700'),
        ];

        foreach ($stock as $utility) {
            $this->assertStringNotContainsString($utility, $pagination);
        }
    }

    public function test_tombol_pagination_memakai_semantik_yang_benar(): void
    {
        $this->seedCatalogWithPages();

        $pagination = $this->paginationMarkup(
            $this->get(route('books.index'))->assertOk()->getContent(),
        );

        // Halaman yang sedang aktif harus ditandai `aria-current="page"`,
        // kalau tidak screen reader hanya membacakan daftar angka biasa
        // tanpa tahu mana posisi sekarang.
        $this->assertStringContainsString('aria-current="page"', $pagination);

        // Tombol nonaktif harus `<span aria-disabled>`, bukan `<a>` tanpa
        // href: yang kedua masih bisa di-Tab dan masih melompat ke URL halaman
        // yang sama.
        $this->assertStringContainsString('aria-disabled="true"', $pagination);

        // Ikon panah dekoratif, supaya tidak dibacakan sebagai "garis" atau
        // apa pun oleh screen reader.
        $this->assertStringContainsString('sr-only', $pagination);
    }

    public function test_drawer_punya_tombol_tutup(): void
    {
        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-sidebar-close', $content);

        // Tombol tutup harus punya nama yang bisa dibacakan. Ikon SVG saja
        // tanpa label berarti screen reader membacakan "tombol" tanpa
        // keterangan apa pun yang menutupi.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-sidebar-close[^>]*aria-label="[^"]+"/s',
            $content,
            'Tombol tutup drawer tidak punya aria-label.',
        );
    }

    public function test_tombol_tutup_drawer_tidak_menggandakan_kontrol_toggle(): void
    {
        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        // Burger dan tombol tutup adalah dua kontrol yang MENGGANTUNGKAN
        // state yang sama (drawer terbuka/tutup). Kalau keduanya punya
        // `data-sidebar-toggle`, keduanya akan punya `aria-expanded`, dan
        // nilainya bisa berbeda karena yang satu diperbarui JS dan yang
        // lain tidak. Pembaca layar akan diberi dua jawaban yang berbeda.
        $this->assertSame(
            1,
            substr_count($content, 'data-sidebar-toggle'),
            'Ada lebih dari satu kontrol dengan data-sidebar-toggle. '
            .'Tombol tutup drawer harus memakai data-sidebar-close.',
        );
    }

    public function test_script_mengembalikan_fokus_setiap_menutup_drawer(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        // Menutup drawer lewat backdrop harus mengembalikan fokus ke burger.
        // Tanpa itu, satu klik di backdrop = pengguna keyboard kehilangan
        // posisi fokus dan harus Tab dari awal dokumen lagi.
        $this->assertStringContainsString(
            'closeAndRestoreFocus',
            $js,
            'initSidebar() harus punya jalur menutup yang mengembalikan fokus.',
        );

        // Semua tiga cara menutup harus lewat fungsi yang sama, supaya tidak
        // ada satu jalur yang lupa mengembalikan fokus. Backdrop dan tombol
        // tutup meneruskan fungsinya langsung sebagai handler (bukan
        // membungkusnya), jadi yang dihitung adalah `addEventListener` yang
        // memakai `closeAndRestoreFocus` plus satu panggilan langsung untuk
        // Escape.
        $this->assertSame(
            2,
            substr_count($js, "addEventListener('click', closeAndRestoreFocus)"),
            'Backdrop dan tombol tutup harus meneruskan closeAndRestoreFocus '
            .'langsung sebagai handler, bukan membungkusnya sendiri.',
        );

        $this->assertMatchesRegularExpression(
            "/if \(event\.key === 'Escape'\) \{\s*closeAndRestoreFocus\(\);/",
            $js,
            'Escape harus menutup drawer lewat closeAndRestoreFocus().',
        );

        // Fokus harus berpindah ke dalam drawer saat dibuka. Drawer ini
        // `<aside>`, bukan `<dialog>`, jadi browser tidak memberi FocusTrap
        // gratis.
        $this->assertMatchesRegularExpression(
            '/if \(open\) \{\s*\(closeButton \?\? focusables\(\)\[0\]\)\?\.focus\(\)/',
            $js,
            'setDrawerOpen() tidak memindahkan fokus ke dalam drawer saat dibuka.',
        );
    }

    public function test_back_to_top_tersembunyi_di_awal_dan_memiliki_aksi_aksesibel(): void
    {
        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-back-to-top[^>]*\shidden(?:\s|>)[^>]*aria-label="Kembali ke atas"[^>]*>/s',
            $content,
            'Tombol harus tersembunyi di awal dan memiliki label aksesibel.',
        );
        $this->assertStringContainsString('fixed right-5 bottom-5', $content);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('window.scrollY > threshold', $script);
        $this->assertStringContainsString('window.scrollTo({ top: 0, behavior })', $script);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $script);
    }
}
