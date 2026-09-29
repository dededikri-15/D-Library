<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_bisa_membuka_halaman_depan(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_halaman_depan_menampilkan_nama_aplikasi_dan_katalog(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('Katalog Buku');
    }

    public function test_halaman_memiliki_brand_d_library_dan_sidebar(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('D-Library')
            ->assertSee('data-sidebar', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-sidebar-backdrop', false)
            ->assertSee('btn-blue-primary', false)
            ->assertSee('btn-blue-outline btn-lg">Daftar anggota</a>', false);
    }

    public function test_halaman_depan_menampilkan_buku_terbaru(): void
    {
        Book::factory()->create(['title' => 'Laskar Pelangi']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Laskar Pelangi');
    }

    public function test_buku_tidak_aktif_tidak_ditampilkan_di_halaman_depan(): void
    {
        Book::factory()->inactive()->create(['title' => 'Buku Disembunyikan']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Buku Disembunyikan');
    }

    public function test_anggota_melihat_tautan_favorit_di_navbar(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('favorites.index'))
            ->assertSee('Anggota');
    }

    public function test_anggota_tidak_melihat_tautan_management(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $response = $this->actingAs($member)->get(route('home'))->assertOk();

        $response->assertDontSee(route('users.index'));
        $response->assertDontSee(route('books.create'));
    }

    public function test_pustakawan_melihat_tautan_manajemen_pengguna(): void
    {
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('users.index'));
    }

    public function test_navbar_menautkan_ke_halaman_kategori_publik(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('categories.public'), escape: false);
    }
}
