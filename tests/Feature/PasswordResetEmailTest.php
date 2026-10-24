<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_link_notification(): void
    {
        Notification::fake();

        $user = User::factory()->anggota()->create(['email' => 'member@d-library.test']);

        $response = $this->post(route('password.email'), ['email' => 'member@d-library.test']);
        $response->assertRedirect()->assertSessionHas('status');

        Notification::assertSentTo($user, function ($notification) {
            return $notification instanceof ResetPasswordNotification;
        });
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        // Email yang tidak terdaftar: tidak ada notifikasi yang dikirim
        // (mencegah email enumeration)
        $this->post(route('password.email'), ['email' => 'tidak-terdaftar@d-library.test'])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token(): void
    {
        $user = User::factory()->anggota()->create([
            'email' => 'member@d-library.test',
            'password' => Hash::make('lama123456'),
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'email' => 'member@d-library.test',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
            'token' => $token,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertTrue(Hash::check('baru123456', $user->fresh()->password));
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        $this->post(route('password.update'), [
            'email' => 'member@d-library.test',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
            'token' => 'invalid-token',
        ])->assertSessionHasErrors('email');
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->anggota()->create(['email' => 'member@d-library.test']);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), ['email' => 'member@d-library.test'])
                ->assertRedirect();
        }

        // Permintaan ke-4 harus terkena rate limit (429)
        $this->post(route('password.email'), ['email' => 'member@d-library.test'])
            ->assertStatus(429);
    }
}