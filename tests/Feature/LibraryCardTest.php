<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(): User
    {
        return User::factory()->anggota()->create();
    }

    /* ------------------------------------------------------------------
     | Route & akses
     * ----------------------------------------------------------------- */

    public function test_anggota_bisa_mengakses_halaman_kartu(): void
    {
        $this->actingAs($this->anggota())
            ->get('/kartu')
            ->assertOk();
    }

    public function test_staff_dilarang_mengakses_kartu(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get('/kartu')
            ->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get('/kartu')
            ->assertRedirect('/login');
    }

    /* ------------------------------------------------------------------
     | Pembuatan kartu otomatis
     * ----------------------------------------------------------------- */

    public function test_kartu_dibuat_otomatis_saat_pertama_kali_dibuka(): void
    {
        $member = $this->anggota();

        $this->assertDatabaseCount('library_cards', 0);

        $this->actingAs($member)->get('/kartu')->assertOk();

        $this->assertDatabaseHas('library_cards', [
            'user_id' => $member->id,
        ]);
    }

    public function test_nomor_kartu_deterministik_dari_id_user(): void
    {
        $member = $this->anggota();

        $expected = now()->year.'-'.str_pad((string) $member->id, 4, '0', STR_PAD_LEFT);
        $this->assertSame($expected, LibraryCard::numberFor($member));
    }

    public function test_membuka_kartu_dua_kali_tidak_membuat_baris_ganda(): void
    {
        $member = $this->anggota();

        $this->actingAs($member)->get('/kartu')->assertOk();
        $this->actingAs($member)->get('/kartu')->assertOk();

        $this->assertSame(1, LibraryCard::where('user_id', $member->id)->count());
    }

    public function test_masa_berlaku_kartu_default_12_bulan(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        $this->assertTrue($card->valid_until->isSameMonth(now()->addMonthsNoOverflow(12)));
        $this->assertTrue($card->isValid());
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
            ->assertSee(LibraryCard::numberFor($member))
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
}
