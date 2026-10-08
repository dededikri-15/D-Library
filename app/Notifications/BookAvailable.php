<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan dalam aplikasi: buku yang sedang diantre kembali tersedia.
 *
 * Dikirim oleh Action `NotifyWaitingList` kepada SELURUH anggota yang
 * mengantre buku ini — bukan hanya orang pertama (keputusan user). Klaim
 * atomik per entri (`waiting_lists.notified_at`) memastikan satu ronde
 * ketersediaan hanya mengirim satu notifikasi per anggota.
 *
 * Lihat kontrak payload di `LoanBorrowed`.
 */
class BookAvailable extends Notification
{
    use Queueable;

    public function __construct(public Book $book)
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
        return [
            'type' => 'book_available',
            'book_id' => $this->book->getKey(),
            'book_title' => $this->book->title,
            // Langsung ke halaman detail: dari situ anggota bisa segera
            // menekan tombol pinjam selama stoknya masih ada.
            'url' => route('books.show', $this->book),
        ];
    }
}
