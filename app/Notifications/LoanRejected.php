<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Peminjaman anggota DIHAPUS oleh pustakawan (koreksi administratif).
 *
 * Tanpa notifikasi ini anggota hanya menemukan riwayatnya hilang begitu saja
 * pada kali berikutnya membuka halaman. Alasan disimpan sebagai KODE
 * (`admin_deleted`), bukan kalimat, supaya teksnya diterjemahkan saat dibaca
 * — lihat kontrak payload di `LoanBorrowed`.
 */
class LoanRejected extends Notification
{
    use Queueable;

    /**
     * Kode alasan "dihapus pustakawan".
     *
     * Disimpan sebagai KODE, bukan kalimat, karena teks notifikasi baru
     * dibuat saat dibaca (lihat kontrak payload di `LoanBorrowed`) — kalau
     * kalimatnya ditulis di sini, anggota yang sedang berbahasa Inggris
     * tetap menerima alasan berbahasa Indonesia.
     */
    public const REASON_ADMIN_DELETED = 'admin_deleted';

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

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $loan = $this->loan;
        $book = $loan->book;

        return [
            'type' => 'rejected',
            'loan_id' => $loan->getKey(),
            'book_id' => $book?->getKey(),
            'book_title' => $book?->title ?? __('loans.book_deleted'),
            'reason' => $this->reason,
            'due_at' => $loan->due_at?->toIso8601String(),
            'url' => route('loans.mine'),
        ];
    }
}
