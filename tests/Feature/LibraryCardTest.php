<?php

namespace Tests\Feature;

use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\CardRenewRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LibraryCardTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(): User
    {
        return User::factory()->anggota()->withBirthDate('1995-06-15')->create();
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

    public function test_nomor_kartu_format_ddmmyy_dari_tanggal_lahir(): void
    {
        $member = $this->anggota();

        // Lahir 15 Jun 1995 → 150695
        $this->assertSame('150695', LibraryCard::numberFor($member));
    }

    public function test_nomor_kartu_tanpa_tanggal_lahir_memicu_exception(): void
    {
        $member = User::factory()->anggota()->create();

        $this->expectException(\RuntimeException::class);
        LibraryCard::numberFor($member);
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

    public function test_halaman_edit_pengguna_menampilkan_checklist_syarat_kartu(): void
    {
        $member = $this->anggota();

        $this->actingAs($this->pustakawan())
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('Syarat perpanjangan')
            ->assertSee('Semua buku sudah dikembalikan')
            ->assertSee('Tidak ada denda yang belum lunas');
    }

    public function test_pustakawan_bisa_memperpanjang_kartu_ketika_syarat_terpenuhi(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertRedirect(route('users.edit', $member));

        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(12)));
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

    public function test_perpanjangan_ditolak_jika_ada_denda_belum_lunas(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        Loan::factory()->returned()->create([
            'user_id' => $member->id,
            'fine' => 5000,
            'fine_paid_at' => null,
        ]);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member))
            ->assertSessionHas('error');

        $this->assertTrue($card->fresh()->valid_until->equalTo($card->valid_until));
    }

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
        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(12)));
    }

    public function test_perpanjangan_dihitung_dari_tanggal_perpanjangan_bukan_tanggal_lama(): void
    {
        $member = $this->anggota();
        $card = LibraryCard::createFor($member);

        // Masa berlaku masih setengah jalan — kalau dihitung dari tanggal
        // lama, hasilnya akan lebih awal dari sekarang + 12 bulan.
        $card->update(['valid_until' => now()->addMonthsNoOverflow(6)]);

        $this->actingAs($this->pustakawan())
            ->post(route('users.card.renew', $member));

        $this->assertTrue($card->fresh()->valid_until->isSameMonth(now()->addMonthsNoOverflow(12)));
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

    public function test_anggota_melihat_checklist_syarat_di_halaman_kartu(): void
    {
        $member = $this->anggota();

        $this->actingAs($member)
            ->get('/kartu')
            ->assertOk()
            ->assertSee('Syarat perpanjangan')
            ->assertSee('Semua buku sudah dikembalikan')
            ->assertSee('Tidak ada denda yang belum lunas');
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
}
