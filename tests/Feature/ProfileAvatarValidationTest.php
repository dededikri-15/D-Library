<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfileAvatarValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    public function test_avatar_must_be_an_image(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
        ]))->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_avatar_respects_size_limit(): void
    {
        $user = User::factory()->anggota()->create();
        $maxKb = (int) config('perpustakaan.uploads.avatar_max_kb');

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('besar.jpg')->size($maxKb + 100),
        ]))->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_avatar_file_is_deleted_when_user_is_deleted(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)->patch(route('profile.update'), $this->profilePayload([
            'email' => $member->email,
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
        ]));

        $path = $member->fresh()->avatar;

        $this->actingAs($staff)->delete(route('users.destroy', $member));

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        Storage::disk(User::avatarDisk())->assertMissing($path);
    }
}
