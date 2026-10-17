<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * Pembuatan pengguna uji yang dipakai bersama antar file test.
 *
 * Sebelumnya lebih dari 10 file test menulis ulang helper `member()` /
 * `librarian()` / `staff()` yang isinya sama persis. Satu trait ini
 * menggantinya: kalau cara membuat pengguna uji berubah, cukup diperbaiki
 * di satu tempat, dan tiap file test jadi lebih pendek tanpa mengurangi
 * satu pun test yang ada.
 */
trait CreatesLibraryUsers
{
    /**
     * Anggota (peran `anggota`) — pembaca yang bisa meminjam buku.
     */
    protected function member(): User
    {
        return User::factory()->anggota()->create();
    }

    /**
     * Pustakawan (peran `pustakawan`) — staf yang mengelola data.
     */
    protected function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    /**
     * Beberapa file test memakai nama `staff()` untuk peran yang sama.
     */
    protected function staff(): User
    {
        return $this->librarian();
    }
}
