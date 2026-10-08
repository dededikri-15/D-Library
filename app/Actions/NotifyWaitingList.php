<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\WaitingList;
use App\Notifications\BookAvailable;

/**
 * Kabari seluruh anggota yang mengantre: buku kini sudah tersedia.
 *
 * ## Kenapa terpisah dari controller?
 *
 * Sama seperti AlertOverdueLoans: deteksi ("buku baru kembali tersedia")
 * dan pengiriman ("siapa yang perlu diberi tahu") dipisah. Titik pemanggil
 * — pengembalian, hapus peminjaman, staf menambah eksemplar — hanya perlu
 * memanggil satu method, dan aturan klaimnya bisa diuji sendiri.
 *
 * ## Kenapa aman dipanggil berulang?
 *
 * Klaimnya atomik per entri: `UPDATE ... WHERE notified_at IS NULL` SEBELUM
 * notify. Dua pengembalian yang berjalan bersamaan berebut klaim yang sama;
 * hanya pemenang yang mengirim. Baris yang sudah diklaim tidak muncul di
 * query berikutnya sampai penandanya di-reset oleh BorrowBook saat buku
 * kembali dipinjam habis (mulai ronde ketersediaan berikutnya).
 *
 * ## Kenapa notifikasi TIDAK dikirim di dalam transaksi?
 *
 * Pola di seluruh proyek: notifikasi selalu dikirim setelah commit
 * (lihat komentar LoanController::returnBook). Kalau dikirim di dalam
 * transaksi dan transaksinya gagal, anggota sudah "dikabari" padahal
 * buku belum tentu kembali tersedia.
 */
class NotifyWaitingList
{
    /**
     * @return int jumlah anggota yang menerima notifikasi
     */
    public function handle(Book $book): int
    {
        // Penghapusan peminjaman tidak selalu membuat buku tersedia
        // (bisa saja masih ada eksemplar lain yang dipinjam). Tanpa cek
        // ini, pesan "sudah tersedia" bisa dikirim untuk buku yang masih
        // habis.
        if (! $book->isAvailable()) {
            return 0;
        }

        $entries = WaitingList::query()
            ->where('book_id', $book->getKey())
            ->whereNull('notified_at')
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($entries as $entry) {
            if ($entry->user === null) {
                continue;
            }

            if (! $this->claim($entry)) {
                continue;
            }

            $entry->user->notify(new BookAvailable($book));
            $sent++;
        }

        return $sent;
    }

    /**
     * Klaim pengiriman secara atomik. Mengembalikan false kalau proses lain
     * sudah mengklaimnya lebih dulu — artinya dia yang mengirim.
     */
    private function claim(WaitingList $entry): bool
    {
        return WaitingList::query()
            ->whereKey($entry->getKey())
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]) === 1;
    }
}
