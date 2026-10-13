<?php

namespace App\Notifications;

use App\Models\LibraryCard;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Anggota mengajukan perpanjangan masa berlaku kartu — diteruskan ke
 * SELURUH PUSTAKAWAN.
 *
 * Alurnya sama dengan pengajuan pengembalian buku (LoanReturnRequested):
 * anggota hanya bisa MENGAJUKAN (syarat dicek otomatis saat tombol ditekan),
 * keputusan akhir ada di tangan pustakawan yang menekan tombol perpanjang
 * di halaman edit pengguna. Lonceng inilah yang memberi tahu mereka ada
 * anggota menunggu. Setelah kartu diperpanjang, stempel pengajuan dihapus
 * (LibraryCard::renew) sehingga anggota tidak lagi terlihat "menunggu".
 */
class CardRenewRequested extends Notification
{
    use Queueable;

    public function __construct(public LibraryCard $card)
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
        $user = $this->card->user;

        return [
            'type' => 'card_renew_requested',
            'user_id' => $user?->getKey(),
            'member_name' => $user?->name ?? '-',
            'card_number' => $this->card->card_number,
            // Mendarat di daftar pengajuan perpanjangan, bukan halaman edit
            // pengguna — di situlah pustakawan mengonfirmasi semua pengajuan.
            'url' => route('library-cards.requests'),
        ];
    }
}
