<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    public function test_login_endpoint_uses_configured_named_rate_limit(): void
    {
        RateLimiter::clear('198.51.100.77');
        config(['perpustakaan.security.login_per_minute' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post('/login', ['email' => 'rate@test.invalid', 'password' => 'wrong'])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post('/login', ['email' => 'rate@test.invalid', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    /* ------------------------------------------------------------------
     | Task 7.5 — password hashing
     * ----------------------------------------------------------------- */

    public function test_passwords_are_never_stored_in_plain_text(): void
    {
        $user = User::factory()->pustakawan()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->fresh()->password);
        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->password));
    }

    public function test_registered_user_password_is_hashed(): void
    {
        $this->post('/register', [
            'name' => 'Anggota Baru',
            'email' => 'baru@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'date_of_birth' => '1995-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'baru@perpustakaan.test')->firstOrFail();

        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
