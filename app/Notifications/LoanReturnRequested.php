<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Anggota mengajukan pengembalian buku — diteruskan ke SELURUH PUSTAKAWAN.
 *
 * Buku belum kembali ke rak sampai staf menandainya di meja sirkulasi,
 * jadi pengajuan ini adalah antrean kerja, bukan sekadar kabar. Lihat
 * kontrak payload di `LoanBorrowed`.
 */
class LoanReturnRequested extends Notification
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
            'type' => 'return_requested',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name ?? '-',
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => route('loans.index', ['status' => Loan::STATUS_BORROWED]),
        ];
    }
}
