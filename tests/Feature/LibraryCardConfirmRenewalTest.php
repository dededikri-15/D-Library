<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardConfirmRenewalTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_konfirmasi_dari_daftar_pengajuan_memperpanjang_kartu_3_bulan(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($this->pustakawan())
            ->from('/kartu/perpanjangan')
            ->post(route('library-cards.renew-confirm', $member))
            ->assertRedirect('/kartu/perpanjangan')
            ->assertSessionHas('status');

        $card = $card->fresh();
        $this->assertTrue($card->valid_until->isSameMonth(now()->addMonthsNoOverflow(3)));
        $this->assertNull($card->renewal_requested_at);
    }

    public function test_konfirmasi_dari_daftar_pengajuan_ditolak_jika_syarat_belum_terpenuhi(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);
        Loan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->pustakawan())
            ->from('/kartu/perpanjangan')
            ->post(route('library-cards.renew-confirm', $member))
            ->assertRedirect('/kartu/perpanjangan')
            ->assertSessionHas('error');

        $this->assertTrue($card->fresh()->valid_until->equalTo($card->valid_until));
        $this->assertNotNull($card->fresh()->renewal_requested_at);
    }

    public function test_anggota_dilarang_mengonfirmasi_pengajuan(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member);

        $this->actingAs($member)
            ->post(route('library-cards.renew-confirm', $member))
            ->assertForbidden();
    }

    public function test_pengajuan_yang_selesai_tidak_lagi_muncul_di_daftar(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member); // tanpa renewal_requested_at

        $this->actingAs($this->pustakawan())
            ->get('/kartu/perpanjangan')
            ->assertOk()
            ->assertDontSee($member->name)
            ->assertSee('Belum ada pengajuan perpanjangan');
    }

    public function test_teks_halaman_kartu_menyebut_perpanjangan_3_bulan(): void
    {
        $this->actingAs($this->anggota())
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Setiap perpanjangan menambah masa berlaku 3 bulan');
    }

    public function test_teks_panel_pustakawan_menyebut_perpanjangan_3_bulan(): void
    {
        $member = $this->anggota();

        $this->actingAs($this->pustakawan())
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('setiap perpanjangan menambah 3 bulan');
    }
}
