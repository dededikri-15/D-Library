<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pengingat jatuh tempo lewat email, dikirim oleh command `loans:remind`.
 *
 * Dua jenis pesan dalam satu kelas, karena isi hampir sama (judul buku,
 * tanggal jatuh tempo, anggota yang meminjam) — hanya kalimat dan
 * kesesuaian urgensi yang berbeda. Sengaja TIDAK di-queue: pengirimannya
 * dari scheduler, volume kecil (satu email per peminjaman per peristiwa),
 * dan tanpa worker antrian email akan menumpuk tanpa terkirim di server
 * demo. Mailable selamat datang juga dikirim sinkron — satu pola untuk
 * seluruh project.
 *
 * Pemanggilan `->notify()` diawali klaim atomik terhadap kolom penanda di
 * `SendLoanReminders`, supaya scheduler yang jalan dua kali bersamaan tetap
 * hanya mengirim satu email.
 */
class LoanDueReminder extends Notification
{
    public const KIND_DUE_SOON = 'due_soon';

    public const KIND_OVERDUE = 'overdue';

    public function __construct(
        public Loan $loan,
        public string $kind = self::KIND_DUE_SOON,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loan = $this->loan;
        $book = $loan->book;
        $dueDate = $loan->displayDate($loan->due_at)?->format('d M Y') ?? '-';

        $subject = $this->kind === self::KIND_OVERDUE
            ? __('loans.reminder_overdue_subject', ['title' => $book?->title ?? __('loans.book_deleted')])
            : __('loans.reminder_due_soon_subject', [
                'title' => $book?->title ?? __('loans.book_deleted'),
                'date' => $dueDate,
            ]);

        $body = $this->kind === self::KIND_OVERDUE
            ? __('loans.reminder_overdue_body', [
                'name' => $notifiable->name,
                'title' => $book?->title ?? __('loans.book_deleted'),
                'date' => $dueDate,
                'fine' => number_format($loan->liveFine(), 0, ',', '.'),
            ])
            : __('loans.reminder_due_soon_body', [
                'name' => $notifiable->name,
                'title' => $book?->title ?? __('loans.book_deleted'),
                'date' => $dueDate,
            ]);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.loan-reminder', [
                'heading' => $this->kind === self::KIND_OVERDUE
                    ? __('loans.reminder_overdue_title')
                    : __('loans.reminder_due_soon_title'),
                'body' => $body,
                // Petunjuk perpanjangan hanya relevan sebelum terlambat;
                // menawarkannya pada pinjaman yang sudah lewat tempo
                // menyesatkan — server akan menolak perpanjangannya.
                'renewHint' => $this->kind === self::KIND_DUE_SOON && $loan->renewalsLeft() > 0
                    ? __('loans.reminder_renew_hint', ['count' => $loan->renewalsLeft()])
                    : null,
            ]);
    }
}
