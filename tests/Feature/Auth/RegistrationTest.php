<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_user_can_register_and_gets_anggota_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
        ]);

        $response->assertRedirect(route('anggota.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ANGGOTA, $user->role);
    }

    public function test_password_is_stored_hashed_not_plain_text(): void
    {
        $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
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
}
