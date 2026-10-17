<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * Pembuat pengguna uji khas kartu perpustakaan — dipakai bersama oleh
 * seluruh kelas hasil pemecahan LibraryCardTest.
 *
 * `anggota()` memakai tanggal lahir tetap supaya nomor kartu bisa diprediksi
 * (150695) pada test format nomor.
 */
trait ProvidesLibraryCardUsers
{
    protected function anggota(): User
    {
        return User::factory()->anggota()->withBirthDate('1995-06-15')->create();
    }

    protected function pustakawan(): User
    {
        return User::factory()->pustakawan()->create();
    }
}
