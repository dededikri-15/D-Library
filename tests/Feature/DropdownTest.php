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

    /*
    | Laporan bug: panel notifikasi terbuka "setengah" / tidak kelihatan.
    | Akar masalahnya adalah urutan lapisan. Panel dropdown memang `z-50`,
    | tapi ia anak topbar sehingga stacking context-nya ikut topbar (`z-30`),
    | sementara sidebar desktop juga `z-50` — akhirnya sidebar menindih panel.
    | Karena itu sidebar desktop diturunkan ke `z-20` (tetap di atas konten
    | utama yang tanpa z-index, kalah dari topbar). Di mobile sidebar harus
    | tetap `z-50` supaya drawer menutupi konten.
    */
    public function test_sidebar_dihitung_di_bawah_topbar_di_desktop(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $html = $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<aside[^>]*\bz-50\b/',
            $html,
            'Sidebar mobile harus tetap z-50 agar drawer menutupi konten.',
        );

        $this->assertMatchesRegularExpression(
            '/<aside[^>]*lg:z-20\b/',
            $html,
            'Sidebar desktop harus lg:z-20 supaya tidak menindih panel dropdown topbar.',
        );
    }

    public function test_sumber_js_mengunci_panel_dropdown_ke_dalam_layar(): void
    {
        $js = file_get_contents(base_path('resources/js/app.js'));

        $this->assertStringContainsString(
            'const clamp = () => {',
            $js,
            'Panel dropdown butuh clamp() agar tidak keluar viewport.',
        );

        $this->assertStringContainsString(
            'menu.offsetWidth',
            $js,
            'Ukuran panel harus memakai offsetWidth, bukan rect yang masih '
            .'terpengaruh animasi scale keyframe pop.',
        );

        $this->assertStringContainsString(
            'getComputedStyle(menu).right',
            $js,
            'Jangkar kiri/kanan dibaca dari computed style supaya tidak salah geser.',
        );

        $this->assertStringContainsString(
            "window.addEventListener('resize'",
            $js,
            'Panel yang terbuka harus dikunci ulang saat layar berubah ukuran.',
        );
    }

    public function test_lebar_panel_dropdown_dibatasi_lebar_layar(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertStringContainsString(
            'max-w-[calc(100vw-1.5rem)]',
            $css,
            'Panel dropdown harus dibatasi lebar viewport-nya, bukan cuma '
            .'min-w-56, agar isinya tidak terpotong di layar sempit.',
        );
    }
}
