<?php

namespace App\Actions;

use App\Models\Loan;

/**
 * Menandai peminjaman yang sudah lewat jatuh tempo sebagai `overdue`.
 *
 * ## Kenapa perlu ada?
 *
 * Kolom `loans.status` punya tiga nilai: `borrowed`, `returned`, `overdue`.
 * Tapi waktu terus berjalan, sedangkan `status` di database tidak bergerak
 * sendiri. Buku yang dipinjam hari ini dengan tempo 14 hari akan tetap
 * tertulis `borrowed` selamanya kecuali ada yang mengubahnya. Padahal
 * secara kenyataannya buku itu sudah terlambat.
 *
 * ## Kenapa satu UPDATE, bukan loop?
 *
 * Satu `UPDATE ... WHERE` untuk semua baris sekaligus. Menulisnya per-baris
 * dengan `foreach` akan menembak satu query untuk setiap peminjaman
 * terlambat, dan setiap query punya biaya tetap. Selain itu, kalau ternyata
 * tidak ada yang terlambat, seluruh query tidak perlu dikirim sama sekali.
 *
 *## Kenapa `borrowed_at` tidak disentuh?
 *
 * `due_at` sudah tersimpan sebagai nilai absolut, jadi tidak perlu dihitung
 * ulang. `returned_at` juga tidak diubah: buku yang terlambat masih ada di
 * tangan anggota, dan baru benar-benar "selesai" saat dikembalikan.
 *
 * ## Kenapa status `books` tidak diubah jadi apa pun di sini?
 *
 * Buku terlambat TETAP berstatus `borrowed`, bukan `available`. Buku
 * fisiknya masih di luar pustaka. Kalau diubah jadi tersedia, anggota lain
 * bisa langsung meminjam buku yang belum dikembalikan.
 *
 * ## Kenapa aksi ini juga mengirim notifikasi?
 *
 * Karena inilah titik DETEKSI keterlambatan. Setiap tempat yang menyegarkan
 * status (scheduler per jam, halaman peminjaman, beranda) otomatis memberi
 * tahu anggota dan pustakawan lewat AlertOverdueLoans, tanpa user harus
 * membuka halaman tertentu dan tanpa menunggu batch harian jam 08:00.
 * Pengirimannya sendiri idempotent — klaim atomik di AlertOverdueLoans yang
 * menjamin peringatan hanya terkirim sekali per peminjaman.
 */
class MarkOverdueLoans
{
    public function __construct(protected AlertOverdueLoans $alertOverdue)
    {
        //
    }

    /**
     * @return int jumlah peminjaman yang berubah statusnya
     */
    public function handle(): int
    {
        $marked = Loan::query()
            ->where('status', Loan::STATUS_BORROWED)
            // `due_at < now()` (bukan `<=`) supaya jatuh tempo tepat hari ini
            // masih dianggap normal, baru terlambat mulai besok.
            ->where('due_at', '<', now())
            ->update(['status' => Loan::STATUS_OVERDUE]);

        /*
         * Selalu dipanggil, bukan hanya saat `$marked > 0`:
         * peminjaman yang sudah `overdue` tapi peringatannya belum terkirim
         * (mis. karena pengiriman sebelumnya gagal di tengah jalan) tetap
         * tertangani. Kalau tidak ada yang tertunda, query-nya kembali kosong
         * dan biayanya satu SELECT kecil.
         */
        $this->alertOverdue->handle();

        return $marked;
    }
}
