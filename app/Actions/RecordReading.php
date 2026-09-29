<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * Mencatat bahwa seorang user membuka / melanjutkan membaca buku digital.
 *
 * ## Kenapa dipisah dari controller?
 *
 * Ada dua pemanggil dengan aturan berbeda:
 *
 * 1. `BookController@read` dipanggil setiap kali halaman pembaca dibuka.
 *    Ia hanya menyentuh `last_read_at` —posisi halaman TIDAK diubah. Kalau
 *    posisi ikut ditulis di sini, membuka ulang buku yang sudah halfway
 *    akan memundurkan posisi ke halaman 1.
 * 2. `ReadingHistoryController@store` dipanggil saat user menekan
 *    "Simpan posisi". Yang ini boleh menulis `last_page`.
 *
 * Kalau keduanya digabung, salah satunya pasti salah: membuka halaman
 * pembaca akan menimpa posisi, atau menyimpan posisi tidak akan pernah
 * memperbarui waktu baca terakhir.
 *
 * ## Kenapa `firstOrNew` dan bukan `updateOrCreate`?
 *
 * Tabel `reading_histories` punya unique constraint pada
 * (user_id, book_id), jadi satu user hanya boleh punya satu baris per buku.
 *
 * `updateOrCreate` tidak bisa dipakai di sini: ia memakai satu set nilai untuk
 * INSERT sekaligus UPDATE, sedangkan isi `last_page` untuk keduanya berbeda —
 * baris baru harus mencatat halaman yang baru dibuka, baris lama tidak boleh
 * disentuh. Karena itu keduanya dipisah secara eksplisit di bawah.
 */
class RecordReading
{
    /**
     * Catat kunjungan. Posisi baca hanya diisi kalau barisnya baru dibuat.
     *
     * @param  int|null  $page  Halaman yang benar-benar dibuka. Boleh null
     *                          kalau pemanggil tidak tahu (mis. dipanggil dari
     *                          tempat lain); artinya mulai dari halaman 1.
     */
    public function visit(User $user, Book $book, ?int $page = null): ReadingHistory
    {
        $attributes = ['user_id' => $user->id, 'book_id' => $book->id];

        $history = ReadingHistory::query()->where($attributes)->first();

        if ($history instanceof ReadingHistory) {
            /*
             * Baris sudah ada. Hanya waktu bacanya yang diperbarui — kalau
             * `last_page` ikut ditulis, membuka ulang buku yang sudah halfway
             * akan memundurkan posisinya ke halaman 1.
             */
            $history->update(['last_read_at' => now()]);

            return $history;
        }

        try {
            return ReadingHistory::create([
                ...$attributes,
                'last_page' => $this->clampPage($page ?? 1, $book),
                'last_read_at' => now(),
            ]);
        } catch (QueryException) {
            /*
             * Dua request yang benar-benar bersamaan bisa sama-sama melihat
             * "belum ada baris" lalu sama-sama INSERT; yang kalah melanggar
             * unique constraint. Bukan error yang perlu diperbaiki: baris yang
             * menang isinya sama saja, jadi request ini memakainya.
             */
            return ReadingHistory::query()->where($attributes)->firstOrFail();
        }
    }

    /**
     * Simpan posisi baca, tetap atau diperbarui.
     */
    public function savePage(User $user, Book $book, int $page): ReadingHistory
    {
        return ReadingHistory::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'last_page' => $this->clampPage($page, $book),
                'last_read_at' => now(),
            ],
        );
    }

    /**
     * Paksa nomor halaman masuk ke rentang yang masuk akal.
     *
     * Nomor halaman datang dari query string, jadi tidak boleh dipercaya:
     * `?page=abc`, `?page=-3`, dan `?page=999999` semuanya harus aman. Tanpa
     * penjepitan, `?page=999999` akan membuat PDF melompat ke halaman yang
     * tidak ada dan `last_page` menyimpan angka yang tidak pernah benar.
     *
     * Buku tanpa data `pages` (buku fisik yang tidak punya
     * jumlah halaman) tidak membatasi batas atas.
     */
    public function clampPage(int $page, Book $book): int
    {
        $page = max(1, $page);

        $total = (int) $book->pages;

        return $total > 0 ? min($page, $total) : $page;
    }
}
