<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfileAvatarManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    public function test_replacing_avatar_deletes_the_old_file(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('lama.jpg'),
        ]));

        $oldPath = $user->fresh()->avatar;

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('baru.jpg'),
        ]));

        $newPath = $user->fresh()->avatar;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk(User::avatarDisk())->assertMissing($oldPath);
        Storage::disk(User::avatarDisk())->assertExists($newPath);
    }

    public function test_avatar_can_be_removed(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
        ]));

        $path = $user->fresh()->avatar;

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'remove_avatar' => '1',
        ]));

        $this->assertNull($user->fresh()->avatar);
        Storage::disk(User::avatarDisk())->assertMissing($path);
    }

    public function test_new_avatar_wins_over_remove_checkbox(): void
    {
        // Dua-duanya terkirim (mis. checkbox masih tercentang lalu user memilih
        // berkas baru). Berkas baru harus menang, dan berkas lama tetap terhapus.
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('lama.jpg'),
        ]));

        $oldPath = $user->fresh()->avatar;

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('baru.jpg'),
            'remove_avatar' => '1',
        ]));

        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk(User::avatarDisk())->assertMissing($oldPath);
    }
}
