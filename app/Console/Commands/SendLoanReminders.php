<?php

namespace App\Console\Commands;

use App\Actions\MarkOverdueLoans;
use App\Models\Loan;
use App\Notifications\LoanDueReminder;
use Illuminate\Console\Command;

/**
 * Kirim email pengingat jatuh tempo.
 *
 * Dua jenis pengiriman dalam satu command, karena keduanya membutuhkan
 * hal yang sama dan harus dijalankan berurutan:
 *
 * 1. Segarkan status `overdue` dulu (MarkOverdueLoans). Tanpa ini,
 *    peminjaman yang baru saja lewat tempo masih berstatus `borrowed`
 *    dan tidak akan masuk query pemberitahuan keterlambatan.
 * 2. H-1/jatuh tempo: pinjaman aktif yang `due_at`-nya jatuh hari ini
 *    atau besok, dan belum pernah dikirim pengingatnya.
 * 3. Terlambat: pinjaman berstatus `overdue` yang belum pernah
 *    dikirim pemberitahuannya.
 *
 * "Belum pernah dikirim" diwakili kolom penanda (`due_reminder_sent_at`,
 * `overdue_notified_at`). Klaimnya dilakukan dengan UPDATE atomik
 * `... WHERE kolom IS NULL` SEBELUM notify: dua scheduler yang jalan
 * bersamaan akan berebut klaim yang sama, dan hanya pemenang yang
 * mengirim email. Kalau notify dikirim dulu lalu klaimnya, tabrakan
 * dua scheduler berarti dua email identik ke anggota yang sama.
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

            $loan->user->notify(new LoanDueReminder($loan, LoanDueReminder::KIND_DUE_SOON));
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
