<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardRenewalGuardTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_perpanjangan_ditolak_jika_kartu_kedaluwarsa_lewat_masa_tenggang(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::factory()->for($member)->expiredBeyondGrace()->create();

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertSessionHas('error');

        $this->assertTrue($card->fresh()->valid_until->equalTo($card->valid_until));
    }

    public function test_kartu_kedaluwarsa_di_bawah_masa_tenggang_tetap_bisa_diperpanjang(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::factory()->for($member)->expired()->create();

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertSessionHas('status');

        // Dihitung dari tanggal perpanjangan (hari ini), bukan menumpuk
        // di ujung masa berlaku lama.
        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(3)));
    }

    public function test_perpanjangan_dihitung_dari_tanggal_perpanjangan_bukan_tanggal_lama(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        // Masa berlaku masih setengah jalan — kalau dihitung dari tanggal
        // lama, hasilnya akan lebih awal dari sekarang + 3 bulan.
        $card->update(['valid_until' => now()->addMonthsNoOverflow(6)]);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member));

        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(3)));
    }

    public function test_anggota_dilarang_memperpanjang_kartunya_sendiri(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member);

        $this->actingAs($member)
            ->post(route('users.card.renew', $member))
            ->assertForbidden();
    }

    public function test_tombol_perpanjang_tampil_disabled_jika_syarat_belum_terpenuhi(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member);
        Loan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->pustakawan())
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('Penuhi semua syarat di atas terlebih dahulu.')
            ->assertDontSee(route('users.card.renew', $member), false);
    }
}
