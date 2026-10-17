<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardRenewalRulesTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_halaman_edit_pengguna_menampilkan_checklist_syarat_kartu(): void
    {
        $member = $this->anggota();

        $this->actingAs($this->pustakawan())
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('Syarat perpanjangan')
            ->assertSee('Semua buku sudah dikembalikan')
            ->assertSee('Tidak sedang dalam masa sanksi larangan meminjam');
    }

    public function test_pustakawan_bisa_memperpanjang_kartu_ketika_syarat_terpenuhi(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertRedirect(route('users.edit', $member));

        // Perpanjangan menambah `renewal_months` (3 bulan) — bukan
        // `validity_months` (12 bulan) yang dipakai kartu baru.
        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(3)));
    }

    public function test_perpanjangan_ditolak_jika_masih_ada_peminjaman_aktif(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        Loan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertRedirect(route('users.edit', $member))
            ->assertSessionHas('error');

        $this->assertTrue($card->fresh()->valid_until->equalTo($card->valid_until));
    }

    public function test_perpanjangan_ditolak_selama_masa_sanksi(): void
    {
        $member = $this->anggota();
        $member->update(['suspended_until' => now()->addHours(48)]);
        $card = LibraryCard::createFor($member);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertSessionHas('error');

        $this->assertTrue($card->fresh()->valid_until->equalTo($card->valid_until));
    }
}
