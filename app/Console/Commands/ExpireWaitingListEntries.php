<?php

namespace App\Console\Commands;

use App\Models\WaitingList;
use Illuminate\Console\Command;

/**
 * Hapus entri daftar tunggu yang sudah lewat jendela notifikasinya.
 *
 * Entri punya dua keadaan:
 *
 * - `notified_at IS NULL` — masih menunggu buku tersedia. TIDAK PERNAH
 *   dihapus oleh command ini, berapa lama pun dia mengantre: tugasnya
 *   memang menunggu.
 * - `notified_at` terisi — sudah dikabari "buku tersedia" tapi belum
 *   ditindaklanjuti (tidak meminjam, tidak membatalkan). Setelah
 *   `waiting_list.notify_window_hours` jam, kesempatan itu dianggap
 *   lewat dan entri dihapus. Kalau masih mau, anggota tinggal mengantre
 *   lagi pada ronde berikutnya.
 *
 * Tanpa pembersihan ini, tabel menumpuk baris yang sudah tidak relevan
 * dan anggota yang tidak aktif tetap menerima notifikasi setiap ronde.
 *
 * Jadwal: routes/console.php (hourly). Dev lokal: `php artisan schedule:work`.
 */
class ExpireWaitingListEntries extends Command
{
    /**
     * @var string
     */
    protected $signature = 'waiting-lists:expire';

    /**
     * @var string
     */
    protected $description = 'Hapus entri daftar tunggu yang kedaluwarsa (lewat jendela notifikasi)';

    public function handle(): int
    {
        $hours = (int) config('perpustakaan.waiting_list.notify_window_hours');

        $deleted = WaitingList::query()
            ->whereNotNull('notified_at')
            ->where('notified_at', '<', now()->subHours($hours))
            ->delete();

        $this->info("{$deleted} entri antrean kedaluwarsa dihapus.");

        return self::SUCCESS;
    }
}
