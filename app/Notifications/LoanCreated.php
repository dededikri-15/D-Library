<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->loan;
        $book = $loan->book;
        $user = $loan->user;
        $dueDate = $loan->displayDate($loan->due_at)?->format('d M Y') ?? '-';

        return (new MailMessage)
            ->subject('Peminjaman baru')
            ->line(($user?->name ?? 'Anggota') . ' telah meminjam buku: ' . ($book?->title ?? '-'))
            ->line('Batas pengembalian: ' . $dueDate)
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
            'type' => 'created',
            'loan_id' => $loan->id,
            'book_id' => $book?->id,
            'book_title' => $book?->title,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'due_at_formatted' => $loan->displayDate($loan->due_at)?->format('d M Y'),
            'message' => 'Peminjaman baru: ' . ($book?->title ?? 'Buku') . ' oleh ' . ($user?->name ?? '-'),
            'url' => route('loans.index'),
        ];
    }
}
