<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Konfirmasi peminjaman yang DICATAT PUSTAKAWAN atas nama anggota.
 *
 * Anggota tidak memilih sendiri di sini — staf yang mengetik di meja
 * sirkulasi — jadi pemberitahuannya berbentuk "disetujui" beserta batas
 * pengembalian, bukan "berhasil dipinjam" seperti `LoanBorrowed`.
 *
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class LoanApproved extends Notification
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
            'type' => 'approved',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'borrowed_at' => $loan->borrowed_at?->toIso8601String(),
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => route('loans.mine'),
        ];
    }
}
