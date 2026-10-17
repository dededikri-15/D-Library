<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    public function test_blank_password_keeps_the_current_one(): void
    {
        $user = User::factory()->anggota()->create(['password' => 'rahasia-lama-123']);

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'password' => '',
        ]));

        $this->assertTrue(password_verify('rahasia-lama-123', $user->fresh()->password));
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'password' => 'rahasia-baru-456',
            'password_confirmation' => 'rahasia-baru-456',
        ]));

        $this->assertTrue(password_verify('rahasia-baru-456', $user->fresh()->password));
    }

    public function test_user_cannot_update_someone_elses_profile(): void
    {
        // Tidak ada parameter user di route, jadi "mengubah orang lain" mustahil
        // lewat request biasa. Test ini menjaga supaya tidak pernah ada
        // `profile.update` versi dengan `{user}` yang lolos tanpa middleware.
        $other = User::factory()->pustakawan()->create(['name' => 'Tidak Boleh Diganti']);

        $this->actingAs(User::factory()->anggota()->create())
            ->patch(route('profile.update'), [
                'name' => 'Penyusup',
                'email' => $other->email,
                'user_id' => $other->id,
            ]);

        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => 'Tidak Boleh Diganti']);
    }
}
