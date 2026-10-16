<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Buku sudah dikembalikan — konfirmasi ke ANGGOTA pemilik pinjaman.
 *
 * Sengaja kanal `database` saja: pengembalian bukan pengingat, dan anggota
 * sudah melihat toast "Buku berhasil dikembalikan" pada detik yang sama.
 * Email disimpan untuk peristiwa yang butuh perhatian di luar aplikasi
 * (pengingat jatuh tempo) — lihat `LoanDueReminder`.
 *
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class LoanReturned extends Notification
{
    use Queueable;

    public function __construct(public Loan $loan)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $loan = $this->loan;
        $book = $loan->book;

        return [
            'type' => 'returned',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'returned_at' => $loan->returned_at?->toIso8601String(),
            /*
             * Diisi hanya kalau pengembalian ini terlambat — `suspended_until`
             * anggota sudah disegarkan oleh completeReturn sebelum notifikasi
             * dikirim (lihat LoanController::returnBook). Null saat tepat
             * waktu; komponen notification-item menyembunyikan kalimat
             * sanksi kalau nilainya kosong.
             */
            'suspended_until' => $loan->isLateReturn()
                ? $loan->user?->suspended_until?->toIso8601String()
                : null,
            'url' => route('loans.mine'),
        ];
    }
}
