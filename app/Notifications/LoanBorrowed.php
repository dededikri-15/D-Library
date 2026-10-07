<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Konfirmasi peminjaman baru untuk ANGGOTA.
 *
 * Dikirim lewat kanal `database` saja (notifikasi dalam aplikasi), sesuai
 * keputusan project: pengingat jatuh tempo lewat email tetap ditangani
 * `LoanDueReminder`, sedangkan notifikasi aktivitas peminjaman cukup muncul
 * di lonceng/notifikasi aplikasi supaya tidak membanjiri inbox.
 */
class LoanBorrowed extends Notification
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
        $title = $book?->title ?? __('notifications.book_deleted');

        return [
            'type' => 'borrowed',
            'title' => __('notifications.borrowed_title'),
            'message' => __('notifications.borrowed_body', ['title' => $title]),
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title,
            'due_at' => $loan->displayDate($loan->due_at)?->format('d M Y'),
            'url' => route('loans.mine'),
        ];
    }
}
