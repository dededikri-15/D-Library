<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use App\Notifications\CardRenewRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardRenewalRequestTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_anggota_melihat_checklist_syarat_di_halaman_kartu(): void
    {
        $member = $this->anggota();

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Syarat perpanjangan')
            ->assertSee('Semua buku sudah dikembalikan')
            ->assertSee('Tidak sedang dalam masa sanksi larangan meminjam');
    }

    /* ------------------------------------------------------------------
     | Pengajuan perpanjangan (anggota → pustakawan, Task 31.6)
     * ----------------------------------------------------------------- */

    public function test_tombol_ajukan_tampil_jika_semua_syarat_terpenuhi(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member);

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Ajukan perpanjangan ke pustakawan')
            ->assertSee(route('library-cards.renew-request'), false);
    }

    public function test_tombol_ajukan_tidak_tampil_jika_syarat_belum_terpenuhi(): void
    {
        $member = $this->anggota();
        LibraryCard::createFor($member);
        Loan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Penuhi semua syarat di atas untuk bisa mengajukan perpanjangan.')
            ->assertDontSee(route('library-cards.renew-request'), false);
    }

    public function test_pengajuan_perpanjangan_mengirim_notifikasi_ke_seluruh_pustakawan(): void
    {
        Notification::fake();

        $member = $this->anggota();
        LibraryCard::createFor($member);
        $pustakawan = $this->pustakawan();

        $this->actingAs($member)
            ->from('/kartu')
            ->post(route('library-cards.renew-request'))
            ->assertRedirect('/kartu')
            ->assertSessionHas('status');

        $card = LibraryCard::where('user_id', $member->id)->firstOrFail();
        $this->assertNotNull($card->renewal_requested_at);

        Notification::assertSentTo($pustakawan, CardRenewRequested::class);
        Notification::assertNotSentTo($member, CardRenewRequested::class);
    }
}
