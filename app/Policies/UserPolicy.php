<?php

namespace App\Policies;

use App\Models\User;

/**
 * Otorisasi manajemen pengguna — SELALU staf.
 *
 * Route `users.*` dijaga middleware `role:pustakawan`; policy ini
 * menyentralisasi aturannya supaya, bila suatu saat ada route pengguna
 * yang tidak lewat middleware itu, aturannya tidak perlu ditulis ulang.
 *
 * Profil yang diedit SENDIRI oleh user (ubah nama, password, avatar) tidak
 * lewat UserController — jalurnya ProfileController dengan aturannya
 * sendiri, dan tidak disentuh policy ini.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $subject): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, User $subject): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->isStaff();
    }
}
