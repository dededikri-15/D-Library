<?php

namespace App\Support;

use App\Models\Book;

/**
 * Pemilih nomor Dewey Decimal Classification (DDC) dari sebuah buku.
 *
 * Nomor DDC ditentukan dua lapis, berurutan:
 *
 * 1. Kata kunci di JUDUL buku — lebih spesifik, jadi diprioritaskan.
 *    "Dasar-Dasar Pemrograman PHP" jatuh ke 005.1, bukan ke 600 milik
 *    kategori Teknologi, karena judulnya menyebut "pemrograman".
 * 2. Kategori buku — jaring pengaman kalau judul tidak mengandung satu
 *    kata kunci pun. "Lembutnya Senja" (Fiksi) tetap dapat 899.22 lewat
 *    jalur ini.
 * 3. Angka 000 (Karya Umum) sebagai fallback terakhir.
 *
 * Urutan kata kunci di bawah MENENTUKAN hasil: yang lebih spesifik harus
 * mendahului yang umum. "pemrograman" misalnya harus dicek sebelum
 * "teknologi", kalau tidak buku pemrograman akan salah masuk rak 600.
 */
final class Ddc
{
    /**
     * Kata kunci judul => nomor DDC. Dicek berurutan, yang pertama cocok menang.
     *
     * @var array<string, string>
     */
    private const KEYWORDS = [
        // 000 — Karya Umum: ilmu komputer. DDC asli menaruh pemrograman di
        // 005, bukan di 600 (teknologi), jadi ini bukan salah letak.
        'basis data|database' => '005.74',
        'pemrograman|program|php|laravel|koding|coding|algoritma|aplikasi|komputer|web|software|siber|jaringan' => '005.1',

        // 500 — Sains dan Matematika.
        'matematika|aljabar|kalkulus|statistika|geometri' => '510',
        'astronomi|galaksi|planet|luar angkasa|bintang' => '520',
        'fisika|mekanika|termodinamika|listrik|gelombang' => '530',
        'kimia|zat|reaksi|periodik' => '540',
        'geologi|fosil|batuan|lautan|gunung' => '550',
        'biologi|genetika|ekologi|hewan|tumbuhan|mikroba' => '570',

        // 200 — Agama.
        'islam|quran|hadis|ibadah|salat|zakat|puasa|nabi|akhlak|masjid' => '297',
        'alkitab|kristen|gereja|paulus' => '230',
        'katolik' => '282',
        'buddha|hindu' => '294',
        'agama|iman|doa|tuhan|spiritual|ketuhanan' => '200',

        // 300 — Ilmu Sosial.
        'hukum|undang-undang|peraturan|advokat|pengadilan' => '340',
        'ekonomi|bisnis|keuangan|investasi|inflasi' => '330',
        'politik|demokrasi|pemerintah|presiden|legislatif' => '320',
        'pendidikan|sekolah|belajar|guru|murid|kurikulum|pembelajaran|literasi|didik' => '370',

        // 600 — Teknologi: kedokteran menurut DDC asli berada di 610.
        'kedokteran|medis|penyakit|kesehatan' => '610',

        // 900 — Sejarah dan Geografi.
        'biografi|riwayat hidup|otobiografi' => '920',
        'geografi|peta|benua' => '910',
        'nusantara|indonesia|sejarah|pahlawan|perang|kerajaan|peradaban|kemerdekaan|kolonial' => '959.8',

        // 800 — Kesusasteraan.
        'puisi|sajak|pantun|syair|cerpen|cerita|novel|roman|esai|sastra|fiksi|hikayat|dongeng' => '899.22',

        // 600 — Teknologi (umum).
        'mesin|teknik|teknologi|rekayasa|robot' => '600',

        // 700 — Seni, Olahraga dan Rekreasi.
        'olahraga|sepak|bulu tangkis|renang' => '796',
        'masak|resep|kuliner' => '641',
    ];

    /**
     * Kategori (slug) => nomor DDC, dipakai kalau judul tidak cocok kata kunci.
     *
     * @var array<string, string>
     */
    private const CATEGORIES = [
        'fiksi' => '899.22',
        'sastra' => '899.22',
        'pendidikan' => '370',
        'teknologi' => '600',
        'sejarah' => '959.8',
        'agama' => '200',
        'sains' => '500',
    ];

    /**
     * Nomor DDC untuk sebuah buku. Selalu mengembalikan string (bukan null),
     * karena kolom inventory_code juga string — caller tidak perlu menangani
     * kasus kosong.
     */
    public static function numberFor(?Book $book): string
    {
        if ($book === null) {
            return '000';
        }

        $title = mb_strtolower(trim((string) $book->title));

        foreach (self::KEYWORDS as $pattern => $number) {
            if ($title !== '' && preg_match('/('.$pattern.')/u', $title) === 1) {
                return $number;
            }
        }

        $slug = mb_strtolower(trim((string) $book->category?->slug));

        return self::CATEGORIES[$slug] ?? '000';
    }
}
