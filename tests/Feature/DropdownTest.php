<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_tidak_melihat_dropdown_menu_akun(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-dropdown', false)
            ->assertDontSee('aria-haspopup="menu"', false);
    }

    public function test_anggota_melihat_dropdown_menu_akun(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_ANGGOTA,
            'name' => 'Budi Santoso',
        ]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-dropdown', false)
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSee('data-dropdown-menu', false)
            ->assertSee('role="menu"', false)
            ->assertSee('Menu akun', false)
            ->assertSee('Budi Santoso')
            ->assertSee($member->email)
            ->assertSee('Anggota');
    }

    public function test_dropdown_anggota_menautkan_ke_halaman_anggota(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('favorites.index'), escape: false)
            ->assertSee(route('loans.mine'), escape: false)
            ->assertSee(route('reading-histories.index'), escape: false);
    }

    public function test_dropdown_pustakawan_menautkan_ke_halaman_staff(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('users.index'), escape: false)
            ->assertSee(route('loans.index'), escape: false)
            ->assertSee(route('books.create'), escape: false);
    }

    public function test_dropdown_anggota_tidak_membocorkan_tautan_staff(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('users.index'), escape: false)
            ->assertDontSee(route('loans.index'), escape: false);
    }

    public function test_dropdown_memuat_tombol_keluar(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('logout'), escape: false);
    }

    public function test_pustakawan_dan_anggota_tetap_bisa_logout(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
