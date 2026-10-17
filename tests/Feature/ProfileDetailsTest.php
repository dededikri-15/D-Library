<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesProfilePayload;
use Tests\TestCase;

class ProfileDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    use ProvidesProfilePayload;

    /* ------------------------------------------------------------------
     | Tampilan
     * ----------------------------------------------------------------- */

    public function test_avatar_image_is_shown_in_sidebar_and_dropdown(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload([
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
        ]));

        $this->actingAs($user)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee($user->fresh()->avatarUrl(), false);
    }

    public function test_navigation_has_link_to_profile(): void
    {
        $user = User::factory()->anggota()->create();

        $content = $this->actingAs($user)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('href="'.route('profile.show').'"', $content);
    }

    /* ------------------------------------------------------------------
     | Tanggal lahir & update kartu perpustakaan otomatis
     * ----------------------------------------------------------------- */

    public function test_user_bisa_menambahkan_tanggal_lahir_di_profil(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload())
            ->assertRedirect(route('profile.show'));

        $this->assertTrue($user->fresh()->hasBirthDate());
    }

    public function test_update_tanggal_lahir_mengupdate_nomor_kartu_lama(): void
    {
        $user = User::factory()->anggota()->create();
        // Kartu lama dengan format lama (tanpa tanggal lahir).
        $card = LibraryCard::create([
            'user_id' => $user->id,
            'card_number' => '2026-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'valid_until' => now()->addYear(),
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload())
            ->assertRedirect(route('profile.show'));

        $this->assertSame('150695', $card->fresh()->card_number);
    }

    public function test_tanggal_lahir_masa_depan_ditolak(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), $this->profilePayload([
                'date_of_birth' => now()->addYear()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('date_of_birth');
    }
}
