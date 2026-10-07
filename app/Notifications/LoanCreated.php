<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan peminjaman baru untuk SELURUH PUSTAKAWAN.
 *
 * Baik peminjaman yang dicatat staf maupun yang diambil anggota sendiri
 * dari halaman buku, meja sirkulasi perlu tahu: buku fisik sedang keluar
 * dari rak dan statusnya berubah.
 *
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class LoanCreated extends Notification
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

        return [
            'type' => 'loan_created',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name ?? '-',
            'borrowed_at' => $loan->borrowed_at?->toIso8601String(),
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => route('loans.index'),
        ];
    }
}
