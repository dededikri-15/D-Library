<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    /* ------------------------------------------------------------------
     | Foto profil
     * ----------------------------------------------------------------- */

    public function test_avatar_is_uploaded_to_disk_and_path_saved(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ]));

        $user->refresh();

        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);

        // Nama file yang disimpan harus di-generate ulang, bukan nama asli dari
        // user: nama asli bisa berisi `../../` atau karakter non-ASCII.
        $this->assertStringNotContainsString('foto.jpg', $user->avatar);

        Storage::disk(User::avatarDisk())->assertExists($user->avatar);
    }

    public function test_avatar_is_optional_and_falls_back_to_gender_illustration(): void
    {
        $user = User::factory()->anggota()->create(['name' => 'Budi Santoso']);

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
        ]));

        $user->refresh();

        $this->assertNull($user->avatar);
        $this->assertNull($user->avatarUrl());
        $this->assertSame('B', $user->initials());

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('avatar-portraits/', false)
            ->assertSee('B', false);
    }

    public function test_changing_gender_updates_the_saved_value_and_fallback_avatar(): void
    {
        $user = User::factory()->anggota()->create([
            'gender' => User::GENDER_LAKI_LAKI,
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload([
                'email' => $user->email,
                'gender' => User::GENDER_PEREMPUAN,
            ]))
            ->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gender' => User::GENDER_PEREMPUAN,
        ]);

        // Potret gender dipilih dari daftar indeks yang sama dengan
        // komponen avatar — pastikan halaman menampilkan potret perempuan.
        $femalePortraits = [1, 3, 5, 7, 9, 10, 12, 14, 16, 18];
        $expected = $femalePortraits[intdiv($user->getKey(), 2) % count($femalePortraits)];

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee("avatar-portraits/{$expected}.png", false)
            ->assertSee('value="perempuan" selected', false)
            ->assertSee('Perempuan');
    }
}
