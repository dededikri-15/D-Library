<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationAvatarTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_mengunggah_avatar_memberi_notifikasi_avatar_replaced(): void
    {
        Notification::fake();
        Storage::fake(User::avatarDisk());
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'avatar_replaced');
    }

    public function test_menghapus_avatar_lewat_tombol_memberi_notifikasi(): void
    {
        Notification::fake();
        $user = $this->member();
        $user->update(['avatar' => 'avatars/foto-contoh.jpg']);

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'avatar_removed');
    }
}
