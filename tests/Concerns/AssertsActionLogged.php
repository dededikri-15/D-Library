<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Notifications\ActionLogged;
use Illuminate\Support\Facades\Notification;

/**
 * Assertion jejak aksi (ActionLogged) yang dipakai bersama oleh seluruh
 * kelas hasil pemecahan ActionNotificationTest.
 */
trait AssertsActionLogged
{
    /**
     * Pastikan user menerima ActionLogged dengan kode tipe tertentu.
     */
    private function assertActionLogged(User $user, string $type): void
    {
        $matched = Notification::sent($user, ActionLogged::class)
            ->contains(fn (ActionLogged $notification) => $notification->type === $type);

        $this->assertTrue(
            $matched,
            "Tidak menemukan notifikasi ActionLogged bertipe '{$type}' untuk user #{$user->id}."
        );
    }

    private function assertNoActionLogged(User $user, string $type): void
    {
        $matched = Notification::sent($user, ActionLogged::class)
            ->contains(fn (ActionLogged $notification) => $notification->type === $type);

        $this->assertFalse(
            $matched,
            "Tidak seharusnya ada notifikasi ActionLogged bertipe '{$type}' untuk user #{$user->id}."
        );
    }
}
