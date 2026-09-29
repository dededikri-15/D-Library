<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Build integrity CSS (pendamping Task 14.9).
 *
 * Latar belakang. Waktu Task 14.9 sempat ada dugaan bahwa menghapus
 * `@source` ke cache Blade membuat puluhan utility dari `@class([...])`
 * hilang dari hasil build — `file:*`, `focus:ring-overdue/30`, dan
 * `bg-available/10` dianggap tidak ter-generate. Dugaan itu ditolak setelah
 * build dengan dan tanpa baris itu dibandingkan: byte sama, jumlah selector
 * sama, nol selisih. Penyebabnya salah alat cek, bukan salah CSS: fungsi
 * escaping nama class di alat cek itu salah, sehingga class yang ADA di CSS
 * dilaporkan HILANG.
 *
 * Jadi tidak ada bug build yang perlu "ditambal" di sini, dan test ini
 * sengaja TIDAK mencoba menguji apakah class utility tertentu muncul di
 * hasil build. Itu butuh menjalankan Vite di dalam PHPUnit: lambat, rapuh,
 * dan tidak sebanding dengan risikonya.
 *
 * Yang dijaga di sini cuma satu aturan yang benar-benar terbukti berbahaya:
 * `@source` harus selalu menunjuk ke file yang dilacak git.
 */
class BuildIntegrityTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(base_path('resources/css/app.css'));
    }

    /**
     * Semua path yang ditulis di `@source`, relatif terhadap folder CSS.
     */
    private function sourcePaths(): array
    {
        preg_match_all(
            "/@source\s+(?:inline\()?['\"]([^'\"]+)['\"]/",
            $this->css(),
            $matches,
        );

        return $matches[1];
    }

    public function test_tidak_ada_safelist_inline(): void
    {
        // Safelist `@source inline("...")` sempat dibuat untuk "menambal" 20
        // utility yang ternyata tidak pernah hilang. Sekarang terbukti tidak
        // diperlukan, dan justru berbahaya: daftar class yang di-hardcode di
        // app.css adalah sumber kebenaran kedua, dan dia bisa basi diam-diam
        // kalau nama class di view berubah.
        $this->assertStringNotContainsString(
            '@source inline(',
            $this->css(),
            'Safelist @source inline() tidak diperlukan. Class di @class([...]) '
            .'tetap terbaca karena Tailwind memindai teks file .blade.php.',
        );
    }

    public function test_source_tidak_menunjuk_ke_path_yang_gitignored(): void
    {
        // Dua path ini yang paling berbahaya:
        //
        // - `storage/framework/views`: gitignored, jadi isinya berbeda antara
        //   mesin ini dan clone baru. Kalau ada di sini, output build bisa
        //   berubah tergantung cache Blade sedang hangat atau tidak.
        // - `vendor`: ikut gitignored. Class di sana bisa berubah setiap kali
        //   `composer update`, dan proyek ini sengaja memakai view pagination
        //   sendiri (Task 14.9) sehingga view bawaan vendor tidak relevan.
        foreach ($this->sourcePaths() as $path) {
            $this->assertStringNotContainsString(
                'storage/',
                $path,
                "@source '{$path}' menunjuk ke folder gitignored storage/. "
                .'Output build jadi tidak reproducible.',
            );

            $this->assertStringNotContainsString(
                'vendor/',
                $path,
                "@source '{$path}' menunjuk ke folder gitignored vendor/.",
            );
        }
    }

    public function test_semua_source_menunjuk_ke_file_yang_benar_ada(): void
    {
        $paths = $this->sourcePaths();

        $this->assertNotEmpty($paths, 'Tidak ada @source sama sekali di app.css.');

        foreach ($paths as $path) {
            // Nol byte, tapi glob-nya harus benar-benar mengenai sesuatu. Kalau
            // path-nya salah ketik, Tailwind tidak error, hanya diam-diam tidak
            // memindai apa pun — dan itu gejala yang paling mahal, karena
            // gejalanya baru muncul jauh nanti sebagai class yang tidak punya
            // gaya.
            $matches = glob(base_path('resources/css/'.$path), GLOB_BRACE);

            $this->assertNotEmpty(
                $matches,
                "@source '{$path}' tidak mengenai satu pun file dari resources/css. "
                .'Periksa path-nya: glob yang salah ketik tidak akan menghasilkan error.',
            );
        }
    }

    public function test_blok_komentar_css_terutup_dengan_benar(): void
    {
        // Jebakan yang sempat hampir reinstated: menulis glob `@source` di
        // dalam komentar CSS. Glob itu memuat karakter `*` diikuti `/`, yaitu
        // `*/`, dan itu MENUTUP blok komentar lebih awal. Akibatnya seluruh
        // sisa file berubah dari komentar jadi CSS hidup, dan build gagal
        // dengan pesan yang sama sekali tidak mengarah ke penyebabnya
        // ("Unterminated string"), padahal file-nya terlihat utuh di editor.
        //
        // Aturannya: di dalam komentar, sebut foldernya saja. Jangan tulis
        // glob.
        $depth = 0;
        $openedAt = null;

        foreach (explode("\n", $this->css()) as $index => $line) {
            foreach (preg_split('#(/\*|\*/)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE) as $token) {
                if ($token === '/*') {
                    if ($depth === 0) {
                        $openedAt = $index + 1;
                    }

                    $depth++;
                } elseif ($token === '*/') {
                    $depth--;

                    if ($depth < 0) {
                        $this->fail(
                            'Baris '.($index + 1).' menutup blok komentar yang tidak pernah dibuka. '
                            .'Kemungkinan ada glob `@source` yang ditulis di dalam komentar CSS.',
                        );
                    }
                }
            }
        }

        $this->assertSame(
            0,
            $depth,
            $depth === 1
                ? "Komentar yang dibuka di baris {$openedAt} tidak pernah ditutup."
                : "Ada {$depth} blok komentar yang tidak tertutup di app.css.",
        );
    }
}
