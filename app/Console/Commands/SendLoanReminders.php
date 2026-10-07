<?php

namespace App\Console\Commands;

use App\Actions\MarkOverdueLoans;
use App\Models\Loan;
use App\Notifications\LoanDueReminder;
use App\Notifications\LoanDueSoon;
use Illuminate\Console\Command;

/**
 * Kirim email pengingat jatuh tempo.
 *
 * Dua jenis pengiriman dalam satu command, karena keduanya membutuhkan
 * hal yang sama dan harus dijalankan berurutan:
 *
 * 1. Segarkan status `overdue` dulu (MarkOverdueLoans). Tanpa ini,
 *    peminjaman yang baru saja lewat tempo masih berstatus `borrowed`
 *    dan tidak akan masuk query pemberitahuan keterlambatan. Penyegaran
 *    itu sekaligus mengirim notifikasi lonceng keterlambatan lewat
 *    AlertOverdueLoans — kolom penandanya terpisah, jadi tidak berebut
 *    dengan email di command ini.
 * 2. H-1/jatuh tempo: pinjaman aktif yang `due_at`-nya jatuh hari ini
 *    atau besok, dan belum pernah dikirim pengingatnya — berupa email
 *    (LoanDueReminder) dan notifikasi lonceng (LoanDueSoon).
 * 3. Terlambat: email pemberitahuan untuk pinjaman berstatus `overdue`
 *    yang belum pernah dikirim emailnya.
 *
 * "Belum pernah dikirim" diwakili kolom penanda (`due_reminder_sent_at`,
 * `overdue_notified_at`, plus `overdue_alerted_at` untuk lonceng).
 * Klaimnya dilakukan dengan UPDATE atomik `... WHERE kolom IS NULL`
 * SEBELUM notify: dua scheduler yang jalan bersamaan akan berebut klaim
 * yang sama, dan hanya pemenang yang mengirim. Kalau notify dikirim dulu
 * lalu klaimnya, tabrakan dua scheduler berarti dua email identik ke
 * anggota yang sama.
 *
 * Jadwal: `routes/console.php` — setiap hari jam 08:00.
 * Dev lokal: jalankan `php artisan schedule:work`.
 */
class SendLoanReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'loans:remind';

    /**
     * @var string
     */
    protected $description = 'Kirim email pengingat jatuh tempo (hari ini/besok) dan pemberitahuan keterlambatan';

    public function handle(MarkOverdueLoans $markOverdue): int
    {
        $markOverdue->handle();

        $dueSoon = $this->sendDueSoon();
        $overdue = $this->sendOverdue();

        $this->info(sprintf(
            '%d pengingat jatuh tempo, %d pemberitahuan keterlambatan terkirim.',
            $dueSoon,
            $overdue,
        ));

        return self::SUCCESS;
    }

    private function sendDueSoon(): int
    {
        /*
         * Jendela waktunya sengaja "sekarang sampai akhir besok", bukan
         * "24 jam ke depan": dengan jadwal harian jam 08:00, pinjaman yang
         * jatuh tempo besok jam 14:00 berada di luar jendela 24 jam biasa
         * dan pengingatnya tidak akan pernah terkirim.
         */
        $loans = Loan::query()
            ->active()
            ->whereHas('book')
            ->whereBetween('due_at', [now(), now()->addDay()->endOfDay()])
            ->with(['user', 'book'])
            ->get();

        $sent = 0;

        foreach ($loans as $loan) {
            if ($loan->user === null) {
                continue;
            }

            if (! $this->claim($loan, 'due_reminder_sent_at')) {
                continue;
            }

            // Email dan notifikasi lonceng berangkat dari klaim YANG SAMA,
            // jadi keduanya tidak mungkin berpisah: email terkirim tanpa
            // lonceng (atau sebaliknya) hanya terjadi kalau notify kedua
            // gagal di tengah jalan, bukan karena jadwal.
            $loan->user->notify(new LoanDueReminder($loan, LoanDueReminder::KIND_DUE_SOON));
            $loan->user->notify(new LoanDueSoon($loan));
            $sent++;
        }

        return $sent;
    }

    private function sendOverdue(): int
    {
        $loans = Loan::query()
            ->overdue()
            ->whereHas('book')
            ->with(['user', 'book'])
            ->get();

        $sent = 0;

        foreach ($loans as $loan) {
            if ($loan->user === null) {
                continue;
            }

            if (! $this->claim($loan, 'overdue_notified_at')) {
                continue;
            }

            $loan->user->notify(new LoanDueReminder($loan, LoanDueReminder::KIND_OVERDUE));
            $sent++;
        }

        return $sent;
    }

    /**
     * Klaim pengiriman secara atomik. Mengembalikan false kalau kolom penanda
     * sudah terisi — artinya proses lain (atau run sebelumnya) sudah
     * mengirimnya.
     */
    private function claim(Loan $loan, string $column): bool
    {
        return Loan::query()
            ->whereKey($loan->getKey())
            ->whereNull($column)
            ->update([$column => now()]) === 1;
    }
}
