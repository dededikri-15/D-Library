<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_user_can_register_and_gets_anggota_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ]);

        $response->assertRedirect(route('anggota.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ANGGOTA, $user->role);
        $this->assertSame(User::GENDER_LAKI_LAKI, $user->gender);
    }

    public function test_password_is_stored_hashed_not_plain_text(): void
    {
        $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ]);

        $user = User::where('email', 'siti@example.com')->firstOrFail();

        $this->assertNotSame('rahasia-kuat-123', $user->password);
        $this->assertTrue(password_verify('rahasia-kuat-123', $user->password));
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->post('/register', [
            'name' => 'Andi',
            'email' => 'andi@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'berbeda-sekali',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'kembar@example.com']);

        $this->post('/register', [
            'name' => 'Kembar',
            'email' => 'kembar@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_cannot_self_assign_staff_role_via_request(): void
    {
        // Role di luar payload tervalidasi harus diabaikan.
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'role' => User::ROLE_PUSTAKAWAN,
        ]);

        $user = User::where('email', 'penyusup@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_ANGGOTA, $user->role);
        $this->assertFalse($user->isStaff());
    }

    public function test_authenticated_user_is_redirected_away_from_register_page(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get('/register')
            ->assertRedirect();
    }

    /* ------------------------------------------------------------------
     | Foto profil saat pendaftaran (opsional)
     * ----------------------------------------------------------------- */

    public function test_register_form_declares_multipart_encoding(): void
    {
        // Tanpa `enctype`, field `avatar` hilang sebelum sampai ke PHP: user
        // mengira gagal mengunggah, padahal berkasnya tidak pernah terkirim.
        $this->get('/register')
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="avatar"', false)
            ->assertSee('name="gender"', false);
    }

    public function test_registration_succeeds_without_avatar(): void
    {
        $this->post('/register', [
            'name' => 'Tanpa Foto',
            'email' => 'tanpafoto@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ])->assertRedirect(route('anggota.dashboard'));

        $user = User::where('email', 'tanpafoto@example.com')->firstOrFail();

        $this->assertNull($user->avatar);
        $this->assertNull($user->avatarUrl());
    }

    public function test_registration_can_include_avatar(): void
    {
        $this->post('/register', [
            'name' => 'Dengan Foto',
            'email' => 'denganfoto@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ])->assertRedirect(route('anggota.dashboard'));

        $user = User::where('email', 'denganfoto@example.com')->firstOrFail();

        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk(User::avatarDisk())->assertExists($user->avatar);
    }

    public function test_registration_rejects_non_image_avatar(): void
    {
        $this->post('/register', [
            'name' => 'Jebakan',
            'email' => 'jebakan@example.com',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'avatar' => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jebakan@example.com']);
    }
}
