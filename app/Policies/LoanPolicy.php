<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

/**
 * Otorisasi peminjaman.
 *
 * Route `loans.*` (kecuali `loans.mine.*`) sudah dijaga middleware
 * `role:pustakawan` — policy ini bukan pengganti middleware, tapi
 * SENTRALISASI aturan "siapa boleh menyentuh pinjaman siapa" supaya
 * controller tidak menulis cek kepemilikan yang berbeda-beda bentuk.
 * Aturan yang sama kini bisa diuji langsung lewat `Gate::forUser()`.
 */
class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->isStaff() || $loan->user_id === $user->getKey();
    }

    /**
     * Pustakawan mencatat pengembalian (route `loans.return`).
     */
    public function returnLoan(User $user, Loan $loan): bool
    {
        return $user->isStaff();
    }

    /**
     * Perpanjangan: anggota hanya untuk pinjamannya sendiri, pustakawan
     * untuk siapa pun. Dipakai BERSAMA oleh `loans.renew` (staf) dan
     * `loans.mine.renew` (anggota) — kedua route memanggil method yang sama.
     */
    public function renew(User $user, Loan $loan): bool
    {
        return $user->isPustakawan() || $loan->user_id === $user->getKey();
    }

    /**
     * Pengajuan pengembalian oleh anggota — selalu atas pinjaman sendiri.
     */
    public function requestReturn(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->getKey();
    }

    /**
     * Hapus catatan peminjaman (koreksi administratif) — staf saja.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->isStaff();
    }
}
