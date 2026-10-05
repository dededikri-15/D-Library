<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman profil + foto profil.
 *
 * Yang dijaga di sini:
 *
 * 1. Otorisasi. `/profil` harus terbuka untuk anggota DAN pustakawan (mengatur
 *    profil bukan hak khusus staff), tapi tetap tertutup untuk tamu.
 * 2. Role tidak bisa dinaikkan sendiri. Ini sebabnya `ProfileRequest` tidak
 *    punya field `role` sama sekali — test ini yang menjaga keputusan itu.
 * 3. Avatar: bisa diunggah, bisa diganti (berkas lama hilang), bisa dilepas,
 *    dan hilang juga waktu akunnya dihapus. Berkas yang nyangkut di storage
 *    adalah kebocoran yang tidak terlihat tapi nyata.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    /**
     * @return array<string, mixed>
     */
    protected function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
        ], $overrides);
    }

    /* ------------------------------------------------------------------
     | Akses halaman
     * ----------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_anggota_can_open_profile_page(): void
    {
        $user = User::factory()->anggota()->create(['name' => 'Anggota Satu']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('name="gender"', false)
            ->assertSee('value="laki-laki" selected', false)
            ->assertSee('Anggota Satu');
    }

    public function test_pustakawan_can_open_profile_page(): void
    {
        $user = User::factory()->pustakawan()->create(['name' => 'Petugas Satu']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Petugas Satu');
    }

    public function test_profile_page_form_declares_multipart_encoding(): void
    {
        // Tanpa `enctype`, field `avatar` hilang sebelum sampai ke PHP dan tidak
        // ada error yang muncul karena validasinya juga tidak pernah jalan.
        $content = $this->actingAs(User::factory()->anggota()->create())
            ->get(route('profile.show'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('enctype="multipart/form-data"', $content);
        $this->assertStringContainsString('name="avatar"', $content);
    }

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
            ->assertSee('avatar-sprite.png', false)
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

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('background-position: 25% 0%;', false)
            ->assertSee('value="perempuan" selected', false)
            ->assertSee('Perempuan');
    }

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
}
