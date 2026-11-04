<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateLibrarianCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_first_librarian_with_hidden_password_input(): void
    {
        $this->artisan('library:create-librarian')
            ->expectsQuestion('Nama lengkap pustakawan', 'Pustakawan Pertama')
            ->expectsQuestion('Email', 'PUSTAKAWAN@example.test')
            ->expectsQuestion('Password (input tidak ditampilkan)', 'kata-sandi-rahasia-123')
            ->expectsQuestion('Ulangi password', 'kata-sandi-rahasia-123')
            ->expectsOutputToContain('Akun pustakawan pustakawan@example.test berhasil dibuat.')
            ->assertExitCode(0);

        $user = User::where('email', 'pustakawan@example.test')->firstOrFail();

        $this->assertSame(User::ROLE_PUSTAKAWAN, $user->role);
        $this->assertTrue(password_verify('kata-sandi-rahasia-123', $user->password));
    }

    public function test_command_refuses_to_create_a_second_librarian(): void
    {
        User::factory()->pustakawan()->create();

        $this->artisan('library:create-librarian')
            ->expectsOutputToContain('Akun pustakawan sudah ada.')
            ->assertExitCode(1);

        $this->assertSame(1, User::pustakawan()->count());
    }

    public function test_command_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('library:create-librarian')
            ->expectsQuestion('Nama lengkap pustakawan', 'Pustakawan Pertama')
            ->expectsQuestion('Email', 'pustakawan@example.test')
            ->expectsQuestion('Password (input tidak ditampilkan)', 'kata-sandi-rahasia-123')
            ->expectsQuestion('Ulangi password', 'kata-sandi-berbeda')
            ->expectsOutputToContain('Konfirmasi kata sandi')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'pustakawan@example.test']);
    }

    public function test_librarian_registration_requires_a_signed_in_librarian(): void
    {
        $this->get('/register/pustakawan')->assertRedirect(route('login'));
        $this->post('/register/pustakawan')->assertRedirect(route('login'));

        $member = User::factory()->anggota()->create();

        $this->actingAs($member)->get('/register/pustakawan')->assertForbidden();
        $this->actingAs($member)->post('/register/pustakawan')->assertForbidden();
    }
}
