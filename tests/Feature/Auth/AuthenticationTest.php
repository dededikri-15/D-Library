<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_pustakawan_can_login_and_is_redirected_to_dashboard(): void
    {
        $librarian = User::factory()->pustakawan()->create([
            'email' => 'pustakawan.lama@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ]);

        $this->post('/login', [
            'email' => 'pustakawan.lama@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($librarian);
    }

    public function test_pustakawan_is_redirected_to_dashboard(): void
    {
        User::factory()->pustakawan()->create([
            'email' => 'pustakawan@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ]);

        $this->post('/login', [
            'email' => 'pustakawan@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_anggota_is_redirected_to_anggota_dashboard(): void
    {
        User::factory()->anggota()->create([
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ]);

        $this->post('/login', [
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ])->assertRedirect(route('anggota.dashboard'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->anggota()->create([
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ]);

        $this->post('/login', [
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'sandi-salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $this->post('/login', [
            'email' => 'tidak-ada@example.com',
            'password' => 'rahasia-kuat-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->anggota()->create([
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ]);

        // Kunci throttle = email + IP, jadi email harus sama di semua percobaan.
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'anggota1@perpustakaan.test',
                'password' => 'sandi-salah',
            ])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 harus diblokir meski kredensial kali ini benar.
        $this->post('/login', [
            'email' => 'anggota1@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->pustakawan()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_when_using_protected_route(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_page_is_hidden_from_authenticated_user(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get('/login')
            ->assertRedirect();
    }
}
