<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->loan;
        $book = $loan->book;
        $returnedAt = $loan->displayDate($loan->returned_at)?->format('d M Y') ?? '-';

        return (new MailMessage)
            ->subject('Buku telah dikembalikan')
            ->line('Buku ' . ($book?->title ?? '-') . ' telah dikembalikan.')
            ->line('Tanggal pengembalian: ' . $returnedAt)
            ->action('Lihat Riwayat', route('loans.mine'));
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
            'loan_id' => $loan->id,
            'book_id' => $book?->id,
            'book_title' => $book?->title,
            'returned_at' => $loan->returned_at?->toISOString(),
            'returned_at_formatted' => $loan->displayDate($loan->returned_at)?->format('d M Y'),
            'message' => 'Buku dikembalikan: ' . ($book?->title ?? 'Buku'),
            'url' => route('loans.mine'),
        ];
    }
}
