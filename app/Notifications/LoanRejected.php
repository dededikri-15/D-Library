<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRejected extends Notification
{
    use Queueable;

    public function __construct(public Loan $loan, public ?string $reason = null)
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

        $mail = (new MailMessage)
            ->subject('Peminjaman ditolak')
            ->line('Peminjaman buku ' . ($book?->title ?? '-') . ' ditolak.');

        if ($this->reason) {
            $mail->line('Alasan: ' . $this->reason);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $loan = $this->loan;
        $book = $loan->book;

        return [
            'type' => 'rejected',
            'loan_id' => $loan->id,
            'book_id' => $book?->id,
            'book_title' => $book?->title,
            'reason' => $this->reason,
            'message' => 'Peminjaman ditolak: ' . ($book?->title ?? 'Buku'),
            'url' => route('loans.mine'),
        ];
    }
}
