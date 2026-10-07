<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Peminjaman sudah lewat jatuh tempo — notifikasi dalam aplikasi.
 *
 * Dikirim tepat di titik DETEKSI (MarkOverdueLoans), bukan menunggu batch
 * email harian, supaya anggota segera diingatkan dan pustakawan bisa menindak
 * lanjuti pada hari yang sama. Emailnya sendiri tetap urusan `loans:remind`
 * jam 08:00 lewat `LoanDueReminder` — dua kanal ini sengaja memakai kolom
 * penanda yang BERBEDA (`overdue_alerted_at` vs `overdue_notified_at`) supaya
 * yang satu tidak memakan klaim yang lain.
 *
 * SATU kelas untuk dua penerima karena isinya hampir sama; yang berbeda
 * hanya penekanan informasinya:
 *
 * - anggota: "segera kembalikan", plus denda yang sedang berjalan,
 * - pustakawan: siapa yang telat, kapan meminjam, kapan jatuh tempo, dan
 *   sudah berapa hari — semua yang dibutuhkan untuk menelepon anggota.
 *
 * Karena itu `type`-nya juga dua: `overdue_member` dan `overdue_staff`.
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class LoanOverdue extends Notification
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
        $user = $loan->user;
        $isStaff = $notifiable->isPustakawan();

        $data = [
            'type' => $isStaff ? 'overdue_staff' : 'overdue_member',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'overdue_days' => $loan->overdueDays(),
            'fine' => $loan->liveFine(),
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => $isStaff
                ? route('loans.index', ['status' => Loan::STATUS_OVERDUE])
                : route('loans.mine'),
        ];

        // Hanya pustakawan yang butuh data pemohon — anggota tidak perlu
        // diberi tahu namanya sendiri, dan daftar notifikasi jadi lebih ringkas.
        if ($isStaff) {
            $data['user_id'] = $user?->getKey();
            $data['user_name'] = $user?->name ?? '-';
            $data['borrowed_at'] = $loan->borrowed_at?->toIso8601String();
        }

        return $data;
    }
}
