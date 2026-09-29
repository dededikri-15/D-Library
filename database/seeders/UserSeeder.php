<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Pustakawan Utama', 'email' => 'pustakawan@perpustakaan.test', 'role' => User::ROLE_PUSTAKAWAN],
            ['name' => 'Anggota Satu', 'email' => 'anggota1@perpustakaan.test', 'role' => User::ROLE_ANGGOTA],
            ['name' => 'Anggota Dua', 'email' => 'anggota2@perpustakaan.test', 'role' => User::ROLE_ANGGOTA],
            ['name' => 'Anggota Tiga', 'email' => 'anggota3@perpustakaan.test', 'role' => User::ROLE_ANGGOTA],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
