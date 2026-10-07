<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->loan;
        $book = $loan->book;
        $dueDate = $loan->displayDate($loan->due_at)?->format('d M Y') ?? '-';

        return (new MailMessage)
            ->subject('Peminjaman disetujui')
            ->line('Peminjaman buku ' . ($book?->title ?? '-') . ' telah disetujui.')
            ->line('Batas pengembalian: ' . $dueDate)
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
            'type' => 'approved',
            'loan_id' => $loan->id,
            'book_id' => $book?->id,
            'book_title' => $book?->title,
            'due_at_formatted' => $loan->displayDate($loan->due_at)?->format('d M Y'),
            'message' => 'Peminjaman disetujui: ' . ($book?->title ?? 'Buku'),
            'url' => route('loans.mine'),
        ];
    }
}
