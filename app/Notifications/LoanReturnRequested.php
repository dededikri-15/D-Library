<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->loan;
        $book = $loan->book;
        $user = $loan->user;

        return (new MailMessage)
            ->subject('Permintaan pengembalian buku')
            ->line(($user?->name ?? 'Anggota') . ' mengajukan pengembalian buku: ' . ($book?->title ?? '-'))
            ->action('Lihat Peminjaman', route('loans.index'));
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
            'loan_id' => $loan->id,
            'book_id' => $book?->id,
            'book_title' => $book?->title,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'message' => 'Permintaan pengembalian: ' . ($book?->title ?? 'Buku') . ' oleh ' . ($user?->name ?? '-'),
            'url' => route('loans.index'),
        ];
    }
}
