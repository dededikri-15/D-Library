<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibrarianRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    public function test_member_registration_page_has_a_separate_librarian_entry_point(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(__('auth.register_title'))
            ->assertSee(__('auth.librarian_register_title'))
            ->assertSee(route('register.librarian'), false);
    }

    public function test_librarian_can_open_a_separate_form_without_a_role_selector(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->get(route('register.librarian'))
            ->assertOk()
            ->assertSee(__('auth.librarian_register_description'))
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('name="role"', false);
    }

    public function test_librarian_registration_creates_librarian_and_stores_avatar(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->post(route('register.librarian.store'), [
                'name' => 'Staf Baru',
                'email' => 'STAF.BARU@example.test',
                'gender' => User::GENDER_PEREMPUAN,
                'date_of_birth' => '1994-06-15',
                'password' => 'kata-sandi-kuat-123',
                'password_confirmation' => 'kata-sandi-kuat-123',
                'role' => User::ROLE_ANGGOTA,
                'avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300),
            ])
            ->assertRedirect(route('users.index'));

        $created = User::where('email', 'staf.baru@example.test')->firstOrFail();

        $this->assertSame(User::ROLE_PUSTAKAWAN, $created->role);
        $this->assertSame(User::GENDER_PEREMPUAN, $created->gender);
        $this->assertTrue(password_verify('kata-sandi-kuat-123', $created->password));
        $this->assertNotNull($created->avatar);
        Storage::disk(User::avatarDisk())->assertExists($created->avatar);
    }

    public function test_librarian_registration_validates_password_confirmation(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->post(route('register.librarian.store'), [
                'name' => 'Staf Baru',
                'email' => 'staf.baru@example.test',
                'gender' => User::GENDER_LAKI_LAKI,
                'password' => 'kata-sandi-kuat-123',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'staf.baru@example.test']);
    }
}
