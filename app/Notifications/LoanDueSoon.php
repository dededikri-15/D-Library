<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pengingat dalam aplikasi: buku mendekati batas pengembalian (H-1/jatuh tempo).
 *
 * Pasangan dari email `LoanDueReminder` — keduanya dikirim dari command
 * `loans:remind` pada klaim yang sama (`due_reminder_sent_at`), jadi tidak
 * mungkin email terkirim tanpa notifikasi lonceng atau sebaliknya, dan tidak
 * mungkin keduanya dobel.
 *
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class LoanDueSoon extends Notification
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
            'type' => 'due_soon',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'due_at' => $loan->due_at?->toIso8601String(),
            'renewals_left' => $loan->renewalsLeft(),
            'url' => route('loans.mine'),
        ];
    }
}
