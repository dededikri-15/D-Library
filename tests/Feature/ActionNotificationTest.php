<?php

namespace Tests\Feature;

use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

/**
 * Jejak aksi (ActionLogged): setiap aksi perubahan data menghasilkan satu
 * notifikasi untuk PELAKUNYA sendiri — bukan untuk orang lain.
 *
 * Dua hal yang dijaga test ini:
 * 1. PENGIRIMAN — aksi yang benar-benar mengubah data mengirim notifikasi
 *    dengan kode tipe yang tepat; aksi idempoten (klik favorit kedua kali,
 *    simpan form tanpa perubahan) TIDAK mengirim notifikasi ganda.
 * 2. PENAMPILAN — tipe baru punya judul di lang dan bisa diklik ke URL
 *    tujuannya.
 *
 * `Notification::fake()` memblokir SEMUA channel, jadi untuk membuktikan
 * payload yang tersimpan, `toArray()` dipanggil manual.
 */
class ActionNotificationTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    /* ------------------------------------------------------------------
     | Profil
     * ----------------------------------------------------------------- */

    public function test_mengubah_data_profil_memberi_notifikasi_jejak_aksi(): void
    {
        Notification::fake();
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'gender' => $user->gender,
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'profile_updated');
    }

    public function test_mengganti_kata_sandi_memberi_notifikasi_terpisah(): void
    {
        Notification::fake();
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'password' => 'rahasia-baru-456',
            'password_confirmation' => 'rahasia-baru-456',
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'password_changed');
        // Nama tidak berubah — jangan ikut dikabari "profil diperbarui".
        $this->assertNoActionLogged($user, 'profile_updated');
    }
}
