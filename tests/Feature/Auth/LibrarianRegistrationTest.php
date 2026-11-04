<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LibrarianRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const INVITATION_CODE = 'kode-undangan-pustakawan-yang-aman';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('perpustakaan.registration.librarian_registration.enabled', true);
        Config::set('perpustakaan.registration.librarian_registration.secret', self::INVITATION_CODE);
    }

    public function test_librarian_registration_has_its_own_form_and_fields(): void
    {
        $this->get(route('librarian.register'))
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('librarian.register.store').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="secret"', false)
            ->assertDontSee('name="gender"', false)
            ->assertDontSee('name="date_of_birth"', false)
            ->assertSee(route('register'), false);
    }

    public function test_first_librarian_can_register_with_the_invitation_code(): void
    {
        $this->post(route('librarian.register.store'), [
            'name' => 'Pustakawan Pertama',
            'email' => 'PUSTAKAWAN@perpustakaan.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'secret' => self::INVITATION_CODE,
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'pustakawan@perpustakaan.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_PUSTAKAWAN, $user->role);
        $this->assertTrue(password_verify('rahasia-kuat-123', $user->password));
        $this->assertSame(1, User::pustakawan()->count());
    }

    public function test_invalid_invitation_code_does_not_create_an_account(): void
    {
        $this->from(route('librarian.register'))
            ->post(route('librarian.register.store'), [
                'name' => 'Bukan Pustakawan',
                'email' => 'bukan@example.test',
                'password' => 'rahasia-kuat-123',
                'password_confirmation' => 'rahasia-kuat-123',
                'secret' => 'kode-salah',
            ])
            ->assertRedirect(route('librarian.register'))
            ->assertSessionHasErrors('secret');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bukan@example.test']);
    }

    public function test_librarian_registration_is_disabled_without_feature_flag_or_secret(): void
    {
        Config::set('perpustakaan.registration.librarian_registration.enabled', false);

        $this->get(route('librarian.register'))
            ->assertOk()
            ->assertDontSee('name="secret"', false);

        $this->post(route('librarian.register.store'), [
            'name' => 'Tidak Dibuat',
            'email' => 'disabled@example.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'secret' => self::INVITATION_CODE,
        ])->assertForbidden();

        Config::set('perpustakaan.registration.librarian_registration.enabled', true);
        Config::set('perpustakaan.registration.librarian_registration.secret', '');

        $this->post(route('librarian.register.store'), [
            'name' => 'Tanpa Kode',
            'email' => 'no-secret@example.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'secret' => self::INVITATION_CODE,
        ])->assertForbidden();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'disabled@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'no-secret@example.test']);
    }

    public function test_only_the_first_librarian_can_register_publicly(): void
    {
        User::factory()->pustakawan()->create();

        $this->get(route('librarian.register'))
            ->assertOk()
            ->assertDontSee('name="secret"', false);

        $this->post(route('librarian.register.store'), [
            'name' => 'Pustakawan Kedua',
            'email' => 'kedua@example.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'secret' => self::INVITATION_CODE,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'kedua@example.test']);
    }

    public function test_members_can_exist_before_the_first_librarian_registers(): void
    {
        User::factory()->anggota()->create();

        $this->post(route('librarian.register.store'), [
            'name' => 'Pustakawan',
            'email' => 'staff@example.test',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'secret' => self::INVITATION_CODE,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.test',
            'role' => User::ROLE_PUSTAKAWAN,
        ]);
    }
}
