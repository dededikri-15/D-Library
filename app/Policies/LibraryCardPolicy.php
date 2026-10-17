<?php

namespace App\Policies;

use App\Models\LibraryCard;
use App\Models\User;

/**
 * Otorisasi kartu perpustakaan.
 *
 * Tiga kemampuan mencerminkan tiga hal yang benar-benar ada di aplikasi:
 *
 * - `view`: pemilik kartu melihat kartunya sendiri; staf boleh melihat
 *   kartu anggota mana pun (mis. dari halaman edit pengguna).
 * - `viewRequests`: daftar pengajuan perpanjangan hanya untuk staf.
 * - `renewCards`: memperpanjang kartu anggota hanya untuk staf.
 *
 * Syarat KELAYAKAN perpanjangan (pinjaman aktif semua kembali, tidak
 * disanksi, tidak terlalu lalu kadaluarsa) TIDAK di policy — aturannya
 * hidup di `LibraryCard::canRenew()` supaya tombol di view dan keputusan
 * server tidak mungkin berbeda.
 */
class LibraryCardPolicy
{
    public function view(User $user, LibraryCard $card): bool
    {
        return $user->isStaff() || $card->user_id === $user->getKey();
    }

    public function viewRequests(User $user): bool
    {
        return $user->isStaff();
    }

    public function renewCards(User $user): bool
    {
        return $user->isStaff();
    }
}
