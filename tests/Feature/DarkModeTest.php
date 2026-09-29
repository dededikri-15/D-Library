<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dark / light mode dan modernisasi tampilan.
 *
 * Test di sini sengaja memeriksa hal yang jauh lebih mudah rusak daripada test
 * fungsional: tampilan terlihat benar saat ditulis, tapi build Vite bisa
 * membuang utility yang tidak terdeteksi pemindai, atau class dinamis
 * (`text-{{ $tone }}`) bisa menghasilkan nol CSS tanpa error sama sekali.
 */
class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        $manifest = public_path('build/manifest.json');

        // Build boleh tidak ada di checkout yang belum `npm run build`, tapi
        // kalau test UI jalan tanpa CSS, semua assertions CSS di sini akan
        // lulus secara palsu. Jadi lebih baik gagal terang.
        $this->assertFileExists($manifest, 'Jalankan `npm run build` sebelum test UI.');

        $assets = json_decode((string) file_get_contents($manifest), true);
        $css = $assets['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($css, 'Manifest Vite tidak punya entri CSS app.css');

        return (string) file_get_contents(public_path('build/'.$css));
    }

    public function test_skrip_tema_dijalankan_sebelum_css_dimuat(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $scriptPosition = strpos($html, 'perpustakaan-theme');
        $stylePosition = strpos($html, 'rel="stylesheet"');

        $this->assertNotFalse($scriptPosition, 'Skrip tema tidak ada di layout.');
        $this->assertNotFalse($stylePosition, 'Tag stylesheet tidak ada di layout.');

        // Kalau urutannya terbalik, halaman berkedip terang beberapa frame
        // setiap kali dibuka dalam mode gelap.
        $this->assertLessThan(
            $stylePosition,
            $scriptPosition,
            'Skrip tema harus ada sebelum <link rel="stylesheet"> supaya tidak ada kedipan.'
        );
    }

    public function test_varian_dark_didaftarkan_sebagai_class_bukan_media_query(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('@custom-variant dark', $this->sourceCss());
        $this->assertStringContainsString(':where(.dark', $css, 'Utility dark: tidak ikut ter-build.');
    }

    public function test_token_warna_light_ikut_design_md(): void
    {
        $source = $this->sourceCss();

        // PRD: #1E293B primary, #4F46E5 aksen, #F8FAFC background, #FFFFFF surface.
        $this->assertStringContainsString('--app-text: #1e293b', $source);
        $this->assertStringContainsString('--app-accent: #4f46e5', $source);
        $this->assertStringContainsString('--app-bg: #f8fafc', $source);
        $this->assertStringContainsString('--app-surface: #ffffff', $source);
    }

    public function test_token_bisa_berbalik_antara_tema(): void
    {
        $css = $this->css();

        // Token harus tetap berupa rujukan variabel, bukan hex yang sudah jadi.
        // Kalau hex-nya di-inline, `bg-surface` akan terkunci ke tema terang
        // dan dark mode tidak akan pernah󰀁ffect halaman.
        $this->assertStringContainsString('--color-surface:var(--app-surface)', $css);
        $this->assertStringContainsString('--color-primary:var(--app-text)', $css);
        $this->assertStringContainsString('--color-tertiary:var(--app-accent)', $css);
    }

    public function test_blok_dark_memakai_spesifisitas_yang_lebih_tinggi_dari_root(): void
    {
        // `.dark` (0,1,0) sama spesifisitasnya dengan `:root` (0,1,0), jadi
        // pemenangnya bergantung pada urutan sumber. `:root.dark` aman.
        $this->assertStringContainsString(':root.dark {', $this->sourceCss());
    }

    public function test_tombol_tema_ada_di_navbar_untuk_semua_peran(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-toggle', escape: false);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-toggle', escape: false);
    }

    public function test_tombol_tema_menyediakan_kedua_ikon(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // Kedua ikon selalu ada di DOM; hanya visibility-nya yang diubah CSS.
        // Kalau ikon ditukar lewat JS, tombol akan kosong saat JS dimuat.
        $this->assertStringContainsString('dark:hidden', $html);
        $this->assertStringContainsString('dark:block', $html);
    }

    public function test_halaman_memakai_komponen_yang_sudah_dukung_dark_mode(): void
    {
        Book::factory()->create(['title' => 'Buku Uji']);

        foreach (['home', 'books.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            // `card` dan `btn` adalah class komponen yang sudah tahu cara
            // gelap-terang. View yang memakainya otomatis ikut tema.
            $this->assertStringContainsString('class="card', $html, "Halaman {$route} tidak memakai .card");
            $this->assertStringContainsString('btn ', $html, "Halaman {$route} tidak memakai .btn");
        }
    }

    public function test_placeholder_cover_menggunakan_gradasi_bukan_kotak_abu_abu(): void
    {
        Book::factory()->create(['title' => 'Tanpa Cover', 'cover' => null]);

        $html = $this->get(route('books.index'))->assertOk()->getContent();

        $this->assertStringContainsString('bg-cover-placeholder', $html, 'Placeholder cover tidak memakai gradasi.');

        // Teks "cover belum tersedia" hanya boleh muncul sebagai label untuk
        // pembaca layar, bukan teks yang terlihat di kartu.
        $this->assertStringNotContainsString('>Cover belum tersedia<', $html);
        $this->assertStringContainsString('Tanpa cover', $html);
    }

    public function test_ikon_memakai_svg_bukan_unicode(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<svg', $html);
        // Karakter Unicode seperti &#9776; (☰) tampil berbeda per OS dan
        // sering tidak sejajar dengan teks di sebelahnya.
        $this->assertStringNotContainsString('&#9776;', $html);
        $this->assertStringNotContainsString('&times;</button>', $html);
    }

    public function test_animasi_hormati_prefers_reduced_motion(): void
    {
        $this->assertStringContainsString('prefers-reduced-motion', $this->sourceCss());
    }

    public function test_tidak_ada_nama_class_dinamis_yang_tidak_akan_terdeteksi_tailwind(): void
    {
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with((string) $file->getFilename(), '.blade.php')) {
                continue;
            }

            // Komentar dibuang dulu. Dokumentasi di dalam kode sering memuat
            // contoh class yang counterproductive, dan class di dalam komentar
            // memang tidak pernah dirender sama sekali.
            $contents = $this->stripComments((string) file_get_contents($file->getPathname()));

            // Tailwind memindai sumber sebagai TEKS. Class yang dirangkai dari
            // variabel terlihat utuh sebagai `text-{{` dan tidak pernah
            // menghasilkan CSS, tanpa error sama sekali.
            if (preg_match('/\b(?:text|bg|border|ring|from|to|via)-\{\{/', $contents, $match)) {
                $offenders[] = $file->getPathname().' -> '.$match[0];
            }
        }

        $this->assertSame([], $offenders, 'Class dinamis tidak akan terdeteksi Tailwind: '.implode(', ', $offenders));
    }

    /**
     * Buang komentar Blade (`{{-- --}}`) dan komentar PHP (`//`, `#`, `*`).
     *
     * Menyalin isi ke string baru lebih aman daripada menulis di atas string
     * yang sama, karena bisa merusak Blade yang sedang dibaca.
     */
    private function stripComments(string $contents): string
    {
        $stripped = preg_replace('/\{\{--.*?--\}\}/s', '', $contents);

        return (string) preg_replace('/^\s*(\*|\/\/|#).*$/m', '', (string) $stripped);
    }

    private function sourceCss(): string
    {
        return (string) file_get_contents(resource_path('css/app.css'));
    }
}
