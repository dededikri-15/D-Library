<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ProvidesLibraryCardUsers;
use Tests\TestCase;

class LibraryCardRequestStatusTest extends TestCase
{
    use ProvidesLibraryCardUsers;
    use RefreshDatabase;

    public function test_pengajuan_kedua_kali_tidak_mengirim_notifikasi_lagi(): void
    {
        Notification::fake();

        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($member)
            ->from('/kartu')
            ->post(route('library-cards.renew-request'))
            ->assertRedirect('/kartu')
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_pengajuan_ditolak_jika_syarat_belum_terpenuhi(): void
    {
        Notification::fake();

        $member = $this->anggota();
        LibraryCard::createFor($member);
        Loan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)
            ->from('/kartu')
            ->post(route('library-cards.renew-request'))
            ->assertRedirect('/kartu')
            ->assertSessionHas('error');

        $card = LibraryCard::where('user_id', $member->id)->firstOrFail();
        $this->assertNull($card->renewal_requested_at);
        Notification::assertNothingSent();
    }

    public function test_setelah_diajukan_tombol_diganti_status_menunggu(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('menunggu konfirmasi pustakawan')
            ->assertDontSee(route('library-cards.renew-request'), false);
    }

    public function test_pustakawan_melihat_lencana_pengajuan_masuk_di_halaman_edit(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);
        $card->update(['renewal_requested_at' => now()]);

        $this->actingAs($this->pustakawan())
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('Pengajuan perpanjangan masuk');
    }
}
