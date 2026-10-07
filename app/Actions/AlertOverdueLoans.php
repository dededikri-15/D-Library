<?php

namespace App\Actions;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanOverdue;

/**
 * Beri tahu anggota dan pustakawan begitu sebuah peminjaman terlambat.
 *
 * ## Kenapa terpisah dari MarkOverdueLoans?
 *
 * MarkOverdueLoans hanya menjawab "baris mana yang berubah status"; aksi ini
 * menjawab "siapa yang perlu diberi tahu". Pemisahannya membuat penanda
 * status tetap satu UPDATE murah, sedangkan pengiriman — yang butuh query
 * user, klaim atomik, dan loop — bisa diuji sendiri.
 *
 * ## Kenapa dipanggil dari MarkOverdueLoans?
 *
 * Karena di situlah titik deteksi. Status `overdue` disegarkan di banyak
 * tempat (scheduler per jam, halaman peminjaman, beranda), dan setiap tempat
 * itu otomatis mengirim peringatan yang belum pernah terkirim — tanpa perlu
 * user membuka halaman tertentu atau menunggu batch harian.
 *
 * ## Kenapa aman dipanggil berulang?
 *
 * Klaimnya atomik: `UPDATE ... WHERE overdue_alerted_at IS NULL` di atas baris
 * peminjaman SEBELUM notify. Dua request yang berjalan bersamaan berebut klaim
 * yang sama; hanya pemenang yang mengirim. Baris yang sudah diklaim tidak
 * pernah muncul di query berikutnya.
 *
 * Kenapa kolom penanda di tabel `loans`, bukan cek ke tabel `notifications`?
 * Alasannya sama dengan `due_reminder_sent_at`: "sudah pernah kirim" harus
 * bisa dijawab satu UPDATE atomik tanpa JOIN ke tabel lain — dan tabel
 * notifikasi bisa saja dibersihkan user lewat tombol hapus di aplikasi.
 */
class AlertOverdueLoans
{
    /**
     * @return int jumlah peminjaman yang peringatannya terkirim
     */
    public function handle(): int
    {
        $loans = Loan::query()
            ->overdue()
            ->whereNull('overdue_alerted_at')
            // Tanpa bukunya, pesan tidak bisa menyebut judul — sama seperti
            // kebijakan email di SendLoanReminders.
            ->whereHas('book')
            ->with(['user', 'book'])
            ->get();

        if ($loans->isEmpty()) {
            return 0;
        }

        // Satu kali ambil, dipakai untuk semua pinjaman dalam batch ini.
        $librarians = User::query()->pustakawan()->get();

        $sent = 0;

        foreach ($loans as $loan) {
            if (! $this->claim($loan)) {
                continue;
            }

            $loan->user?->notify(new LoanOverdue($loan));

            foreach ($librarians as $librarian) {
                $librarian->notify(new LoanOverdue($loan));
            }

            $sent++;
        }

        return $sent;
    }

    /**
     * Klaim pengiriman secara atomik. Mengembalikan false kalau proses lain
     * sudah mengklaimnya lebih dulu — artinya dia yang mengirim.
     */
    private function claim(Loan $loan): bool
    {
        return Loan::query()
            ->whereKey($loan->getKey())
            ->whereNull('overdue_alerted_at')
            ->update(['overdue_alerted_at' => now()]) === 1;
    }
}
