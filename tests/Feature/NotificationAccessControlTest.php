<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoanDueSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class NotificationAccessControlTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    public function test_notifikasi_orang_lain_tidak_bisa_ditandai_bahkan_dengan_id_asli(): void
    {
        $member = $this->member();
        $other = $this->member();
        $other->notify(new LoanDueSoon($this->loanFor($other)));
        $foreign = $other->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $foreign))
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_id_notifikasi_bukan_uuid_menghasilkan_404_bukan_500(): void
    {
        // Kolom id bertipe uuid. Tanpa `whereUuid` pada route, input semacam
        // ini masuk ke query dan di PostgreSQL melempar QueryException -> 500.
        $this->actingAs($this->member())
            ->get('/notifikasi/abc/tandai')
            ->assertNotFound();
    }

    public function test_tandai_semua_sudah_dibaca_menandai_seluruh_baris_sekaligus(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->post(route('notifications.readAll'))
            ->assertRedirect()
            ->assertSessionHas('status', __('notifications.mark_all_done'));

        $this->assertSame(0, $member->notifications()->whereNull('read_at')->count());
    }

    public function test_tamu_didakhalangi_melihat_notifikasi(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.readAll'))->assertRedirect(route('login'));
    }

    public function test_jenis_notifikasi_tidak_dikenal_tetap_tampil_dan_tidak_mengarah_ke_luar(): void
    {
        $member = $this->member();

        // Baris lama / payload asing: type tak dikenal dan `url` bukan milik
        // aplikasi ini. Keduanya tidak boleh membuat halaman rusak maupun
        // mengarahkan user ke domain lain.
        $stored = $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PayloadAsing',
            'data' => ['type' => 'tidak_dikenal', 'book_title' => 'Judul Misterius', 'url' => 'https://contoh-asing.test/x'],
            'created_at' => now(),
        ]);

        // Payload tanpa judul sama sekali: jatuh ke judul generik.
        $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PayloadKosong',
            'data' => ['type' => 'juga_tidak_dikenal'],
            'created_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Judul Misterius')
            ->assertSee(__('notifications.generic_title'), false);

        $this->actingAs($member)
            ->get(route('notifications.read', $stored))
            ->assertRedirect(route('notifications.index'));
    }
}
