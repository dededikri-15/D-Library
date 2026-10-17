<?php

namespace Tests\Concerns;

use App\Models\User;

/**
 * Payload form profil — dipakai bersama oleh kelas hasil pemecahan ProfileTest.
 *
 * Nilai default (nama, email, jenis kelamin, tanggal lahir) disengaja sederhana
 * supaya mudah dioverride per skenario.
 */
trait ProvidesProfilePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'date_of_birth' => '1995-06-15',
        ], $overrides);
    }
}
