<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

/**
 * Perpanjang jatuh tempo peminjaman — dipakai BERSAMA oleh jalur anggota
 * (pinjamannya sendiri) dan jalur pustakawan (pinjamannya siapa pun).
 *
 * Aturan kelayakan ada di `Loan::canRenew()` — tombol di view memakai method
 * yang sama, jadi kasus "tombol terlihat tapi server menolak" hampir mustahil
 * terjadi kecuali saat race (dua perpanjangan bersamaan), yang ditangani
 * `lockForUpdate` di bawah.
 *
 * Otorisasi SIAPA yang boleh memperpanjang TIDAK di sini — itu urusan
 * policy/controller. Action ini hanya memastikan barisnya ditulis dengan
 * benar dan atomik.
 */
class RenewLoan
{
    /**
     * @return string 'renewed' atau 'ineligible' (salah satu syarat canRenew()
     *                terpenuhi saat transaksi berjalan — mis. sudah lewat tempo)
     */
    public function handle(Loan $loan): string
    {
        return DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->canRenew()) {
                return 'ineligible';
            }

            $lockedLoan->update([
                'due_at' => $lockedLoan->renewalDueAt(),
                'renew_count' => $lockedLoan->renew_count + 1,
            ]);

            return 'renewed';
        });
    }
}
