<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

/**
 * Ajukan pengembalian oleh ANGGOTA (pinjamannya sendiri, via tombol
 * "Ajukan pengembalian" di halaman Peminjaman Saya).
 *
 * Pola yang sama dengan BorrowBook/CompleteReturn: transaksi + kunci baris
 * di sini, notifikasi dan pesan flash di controller. Dua anggota (atau
 * anggota + pustakawan) yang menekan tombol pada detik yang sama
 * diserialkan lewat `lockForUpdate`, jadi `return_requested_at` tidak
 * pernah ditimpa dua kali dan pustakawan tidak menerima notifikasi dobel.
 */
class RequestReturn
{
    /**
     * @return string 'requested' (pengajuan baru), 'pending' (sudah pernah
     *                mengajukan), atau 'returned' (sudah selesai — tombol
     *                basi / race dengan pustakawan yang baru mencatat)
     */
    public function handle(Loan $loan): string
    {
        return DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->isActive()) {
                return 'returned';
            }

            if ($lockedLoan->hasReturnRequest()) {
                return 'pending';
            }

            $lockedLoan->update(['return_requested_at' => now()]);

            return 'requested';
        });
    }
}
