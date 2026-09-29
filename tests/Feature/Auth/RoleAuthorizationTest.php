<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pustakawan_can_access_staff_dashboard(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_anggota_cannot_access_staff_dashboard(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_anggota_can_access_own_dashboard(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('anggota.dashboard'))
            ->assertOk();
    }

    public function test_staff_cannot_access_anggota_dashboard(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('anggota.dashboard'))
            ->assertForbidden();
    }

    public function test_pustakawan_can_manage_users(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_anggota_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_management_page_can_filter_by_role(): void
    {
        $librarian = User::factory()->pustakawan()->create(['name' => 'Pustakawan Utama']);
        User::factory()->pustakawan()->create([
            'name' => 'Pustakawan Cadangan',
            'email' => 'cadangan@perpustakaan.test',
        ]);
        User::factory()->anggota()->create(['name' => 'Anggota Satu']);

        // Nama user yang sedang login selalu muncul di header.
        $this->actingAs($librarian)
            ->get(route('users.index', ['role' => User::ROLE_PUSTAKAWAN]))
            ->assertOk()
            ->assertSee('Pustakawan Utama')
            ->assertSee('Pustakawan Cadangan')
            ->assertDontSee('Anggota Satu');
    }

    public function test_book_policy_allows_staff_to_manage_books(): void
    {
        $book = Book::factory()->create();

        $librarian = User::factory()->pustakawan()->create();

        $this->assertTrue($librarian->can('create', Book::class));
        $this->assertTrue($librarian->can('update', $book));
        $this->assertTrue($librarian->can('delete', $book));
    }

    public function test_book_policy_denies_anggota_from_managing_books(): void
    {
        $book = Book::factory()->create();
        $anggota = User::factory()->anggota()->create();

        $this->assertFalse($anggota->can('create', Book::class));
        $this->assertFalse($anggota->can('update', $book));
        $this->assertFalse($anggota->can('delete', $book));
    }

    public function test_role_middleware_rejects_user_without_matching_role(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_unknown_role_value_denies_access(): void
    {
        // Role di luar daftar resmi harus tidak bisa masuk ke area staff.
        $user = User::factory()->create(['role' => 'peretas']);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $user->forceFill(['role' => 'admin'])->save();
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }
}
