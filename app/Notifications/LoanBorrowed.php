<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Konfirmasi peminjaman baru untuk ANGGOTA yang meminjam sendiri.
 *
 * Kanal `database` saja (notifikasi dalam aplikasi). Pengingat jatuh tempo
 * tetap lewat email (`LoanDueReminder`), sedangkan aktivitas peminjaman cukup
 * muncul di lonceng supaya tidak membanjiri inbox.
 *
 * KONTRAK PAYLOAD — berlaku untuk seluruh kelas di folder ini:
 *
 * - `toArray()` hanya menyimpan `type` + parameter mentah (id, judul buku,
 *   tanggal ISO-8601). Judul dan kalimat TIDAK disimpan dalam bahasa apa pun.
 * - Teks dirender saat notifikasi DIBACA oleh `x-notification-item`, dari
 *   `lang/{locale}/notifications.php`. Karena itu user yang mengganti bahasa
 *   setelah notifikasi masuk tetap melihat notifikasi dalam bahasanya.
 * - `type` adalah kunci di `notifications.php`. Kalau type tidak dikenal,
 *   view jatuh ke teks generik, bukan error.
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

        return [
            'type' => 'borrowed',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => route('loans.mine'),
        ];
    }
}
