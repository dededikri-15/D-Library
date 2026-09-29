<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * Modul pengelolaan buku hanya untuk pustakawan.
     * ditangani route terpisah di Kategori 5.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Book $book): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->isStaff();
    }

    /**
     * Apakah user boleh MEMINJAM buku ini untuk dirinya sendiri (Task 10.1).
     *
     * Hanya anggota. Staff tidak punya tombol ini karena mereka mencatat
     * peminjaman lewat halaman /peminjaman, di mana mereka memilih anggotanya
     * sendiri.
     *
     * Syarat "bukunya masih tersedia" TIDAK di sini. Alasannya, ketersediaan
     * bisa berubah sedetik setelah policy dievaluasi, jadi satu-satunya
     * tempat yang benar untuk memeriksanya adalah BorrowBook, di dalam
     * transaksi. Kalau dicek di policy, hasilnya hanya petunjuk tampilan:
     * tombolnya bisa tampil tapi aksi tetap ditolak.
     */
    public function borrow(User $user, Book $book): bool
    {
        return $user->isMember();
    }
}
