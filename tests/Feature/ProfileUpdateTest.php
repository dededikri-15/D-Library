<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    /* ------------------------------------------------------------------
     | Edit data profil
     * ----------------------------------------------------------------- */

    public function test_user_can_update_own_name_and_email(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload([
                'name' => 'Nama Baru',
                'email' => 'baru@perpustakaan.test',
            ]))
            ->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'baru@perpustakaan.test',
        ]);
    }

    public function test_unchanged_email_does_not_conflict_with_own_row(): void
    {
        // `Rule::unique()` tanpa `ignore()` akan membuat user yang tidak
        // mengubah email-nya pun ditolak, karena email-nya bentrok dengan
        // barisnya sendiri.
        $user = User::factory()->anggota()->create([
            'name' => 'Awal',
            'email' => 'tetap@perpustakaan.test',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Akhir',
                'email' => 'tetap@perpustakaan.test',
                'gender' => User::GENDER_LAKI_LAKI,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Akhir']);
    }

    public function test_email_must_not_be_taken_by_another_user(): void
    {
        User::factory()->create(['email' => 'dipakai@perpustakaan.test']);
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload([
                'email' => 'dipakai@perpustakaan.test',
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => 'Campur@Perpustakaan.Test',
        ]));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'campur@perpustakaan.test',
        ]);
    }

    public function test_user_cannot_change_own_role(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'role' => User::ROLE_PUSTAKAWAN,
        ]));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_ANGGOTA,
        ]);
    }
}
