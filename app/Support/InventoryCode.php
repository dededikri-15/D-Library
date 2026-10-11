<?php

namespace App\Support;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Pembentuk kode inventaris eksemplar berformat DDC.
 *
 * Bentuk akhir: `{DDC}/{3 huruf penulis}/{nomor urut 2 digit}`, contoh
 * `959.8/WIJ/01`. Tiga bagian itu dibaca telak oleh pustakawan:
 *
 * - DDC   → di rak mana buku ini disusun (hasil Ddc::numberFor).
 * - Penulis → 3 huruf kata terakhir nama penulis ("Hendra Wijaya" => WIJ),
 *   gaya penanda Cutter yang dipakai banyak perpustakaan Indonesia.
 * - Nomor urut → eksemplar keberapa dari judul tersebut.
 *
 * Nomor urut dihitung dari suffix terbesar yang sudah ada untuk prefix yang
 * sama, sehingga dua buku dengan DDC dan penulis identik tidak pernah
 * berebut kode. Perhitungan memakai `pluck` + parsing di PHP (bukan
 * `MAX()` di database) supaya hasilnya sama di PostgreSQL (dev) dan SQLite
 * (test) — lihat catatan di AGENTS.md tentang perbedaan dua database itu.
 */
final class InventoryCode
{
    /**
     * Prefix kode untuk satu buku, tanpa nomor urut. Contoh: `959.8/WIJ/`.
     */
    public static function prefixFor(Book $book): string
    {
        return Ddc::numberFor($book).'/'.self::authorMark($book).'/';
    }

    /**
     * Kode lengkap untuk satu eksemplar buku (nomor urutnya = 1 + suffix
     * terbesar yang sudah terpakai).
     *
     * Retry 3× menangani balapan langka: dua request bersamaan menghitung
     * suffix yang sama. Constraint unique di database adalah jaring
     * pengaman terakhir; kalau tetap tabrak setelah 3×, QueryException
     * dibiarkan muncul supaya kegagalannya terlihat, bukan diam-diam.
     */
    public static function generate(Book $book): string
    {
        $prefix = self::prefixFor($book);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $code = $prefix.sprintf('%02d', self::nextSequence($prefix));

            if (! BookCopy::query()->where('inventory_code', $code)->exists()) {
                return $code;
            }
        }

        // Tidak mungkin kecuali ada yang menulis kode di luar method ini.
        throw new QueryException('Gagal membuat kode inventaris unik untuk prefix '.$prefix);
    }

    /**
     * Nomor urut berikutnya untuk sebuah prefix = suffix terbesar + 1.
     *
     * Suffix diambil dari potongan kode setelah panjang prefix, lalu dibuang
     * ke int. Kalau ada kode yang tidak berformat (data lama sebelum
     * regenerasi), potongannya gagal jadi int dan diabaikan dengan nilai 0.
     */
    private static function nextSequence(string $prefix): int
    {
        $length = strlen($prefix);

        $highest = BookCopy::query()
            ->where('inventory_code', 'like', $prefix.'%')
            ->pluck('inventory_code')
            ->map(fn (string $code): int => (int) substr($code, $length))
            ->max();

        return ($highest ?? 0) + 1;
    }

    /**
     * 3 huruf penanda penulis dari kata terakhir namanya.
     *
     * "Hendra Wijaya" -> "WIJ", "Budi Santoso" -> "SAN", "Prasetyo" -> "PRA".
     * Kata terakhir dipakai karena pada nama orang Indonesia itu umumnya
     * nama keluarga — bagian yang paling stabil sebagai penanda.
     *
     * Huruf non-alfabet dibuang dulu ("Budi, S." -> "S"), lalu hasilnya
     * dipotong 3 huruf dan ditambah X bila kurang ("S" -> "SXX") supaya
     * panjang penanda selalu 3 dan kode tetap rapi.
     */
    private static function authorMark(Book $book): string
    {
        $name = trim((string) $book->author?->name);

        if ($name === '') {
            return 'XXX';
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $last = Str::upper((string) end($words));
        $letters = Str::of($last)->replaceMatches('/[^A-Z]/', '')->value();
        $mark = Str::substr($letters, 0, 3);

        return str_pad($mark, 3, 'X');
    }
}
