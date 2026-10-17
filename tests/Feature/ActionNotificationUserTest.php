<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationUserTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    /* ------------------------------------------------------------------
     | Pengguna
     * ----------------------------------------------------------------- */

    public function test_kelola_pengguna_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('users.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota-baru@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-12345',
            'password_confirmation' => 'rahasia-12345',
            'role' => User::ROLE_ANGGOTA,
        ])->assertRedirect();
        $this->assertActionLogged($staff, 'user_created');

        $target = User::where('email', 'anggota-baru@perpustakaan.test')->firstOrFail();

        $this->actingAs($staff)->patch(route('users.update', $target), [
            'name' => 'Anggota Diganti',
            'email' => $target->email,
            'gender' => $target->gender,
            'password' => '',
            'role' => User::ROLE_ANGGOTA,
        ])->assertRedirect();
        $this->assertActionLogged($staff, 'user_updated');

        $this->actingAs($staff)->delete(route('users.destroy', $target))->assertRedirect();
        $this->assertActionLogged($staff, 'user_deleted');

        // Yang dikabari pelaku (pustakawan), bukan akun yang diedit.
        $this->assertNoActionLogged($target, 'user_updated');
    }

    /* ------------------------------------------------------------------
     | Favorit & riwayat baca (anggota)
     * ----------------------------------------------------------------- */

    public function test_favorit_ditambah_dan_dihapus_mengirim_jejak_aksi_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $book = Book::factory()->create();

        $this->actingAs($member)->post(route('favorites.store', $book))->assertRedirect();
        $this->assertActionLogged($member, 'favorite_added');

        // Klik kedua: idempoten — tidak ada notifikasi ganda.
        $this->actingAs($member)->post(route('favorites.store', $book))->assertRedirect();
        $this->assertSame(
            1,
            Notification::sent($member, ActionLogged::class)
                ->filter(fn (ActionLogged $n) => $n->type === 'favorite_added')
                ->count()
        );

        $this->actingAs($member)->delete(route('favorites.destroy', $book))->assertRedirect();
        $this->assertActionLogged($member, 'favorite_removed');

        // Hapus kedua kali: baris sudah tidak ada — jejaknya tidak dikarang.
        $this->actingAs($member)->delete(route('favorites.destroy', $book))->assertRedirect();
        $this->assertSame(
            1,
            Notification::sent($member, ActionLogged::class)
                ->filter(fn (ActionLogged $n) => $n->type === 'favorite_removed')
                ->count()
        );
    }
}
