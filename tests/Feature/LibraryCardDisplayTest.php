<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardDisplayTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    /* ------------------------------------------------------------------
     | Guard: tanggal lahir belum diisi
     * ----------------------------------------------------------------- */

    public function test_kartu_tidak_dibuat_jika_tanggal_lahir_kosong(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Lengkapi tanggal lahir terlebih dahulu')
            ->assertSee('Buka halaman profil');

        $this->assertDatabaseCount('library_cards', 0);
    }

    /* ------------------------------------------------------------------
     | Tampilan
     * ----------------------------------------------------------------- */

    public function test_halaman_kartu_menampilkan_nomor_pemegang_dan_status(): void
    {
        $member = $this->anggota();

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('150695')
            ->assertSee($member->name)
            ->assertSee('Kartu aktif');
    }

    public function test_kartu_kedaluwarsa_menampilkan_status_kedaluwarsa(): void
    {
        $member = $this->anggota();
        LibraryCard::factory()->for($member)->expired()->create();

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Kartu sudah kedaluwarsa');
    }

    /* ------------------------------------------------------------------
     | Menu sidebar
     * ----------------------------------------------------------------- */

    public function test_menu_sidebar_anggota_menampilkan_kartu_bukan_kotak_masuk(): void
    {
        $this->actingAs($this->anggota())
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Kartu Perpustakaan')
            ->assertDontSee('Kotak Masuk');
    }

    /* ------------------------------------------------------------------
     | Perpanjangan masa berlaku (pustakawan)
     * ----------------------------------------------------------------- */

    private function pustakawan(): User
    {
        return User::factory()->pustakawan()->create();
    }
}
