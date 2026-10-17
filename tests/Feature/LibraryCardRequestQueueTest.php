<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardRequestQueueTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_memperpanjang_kartu_menghapus_status_pengajuan(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member));

        $this->assertNull($card->fresh()->renewal_requested_at);
    }

    public function test_pustakawan_dilarang_mengajukan_perpanjangan(): void
    {
        $this->actingAs($this->pustakawan())
            ->post(route('library-cards.renew-request'))
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------
     | Halaman daftar pengajuan (pustakawan, menu "Pengajuan Kartu")
     * ----------------------------------------------------------------- */

    public function test_pustakawan_bisa_membuka_daftar_pengajuan(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($this->pustakawan())
            ->get('/kartu/perpanjangan')
            ->assertOk()
            ->assertSee('Pengajuan Perpanjangan Kartu')
            ->assertSee($member->name)
            ->assertSee('150695');
    }

    public function test_anggota_dilarang_membuka_daftar_pengajuan(): void
    {
        $this->actingAs($this->anggota())
            ->get('/kartu/perpanjangan')
            ->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_login_saat_buka_daftar_pengajuan(): void
    {
        $this->get('/kartu/perpanjangan')
            ->assertRedirect('/login');
    }

    public function test_menu_sidebar_pustakawan_menampilkan_pengajuan_kartu(): void
    {
        $this->actingAs($this->pustakawan())
            ->get('/kartu/perpanjangan')
            ->assertOk()
            ->assertSee('Pengajuan Kartu');
    }
}
