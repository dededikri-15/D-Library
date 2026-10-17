<?php

namespace Tests\Feature;

use App\Notifications\LoanDueSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class NotificationBellBehaviorTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    public function test_lonceng_tampil_di_topbar_untuk_user_masuk(): void
    {
        $this->actingAs($this->member())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('notifications.index'), false);
    }

    public function test_membuka_lonceng_menandai_semua_sudah_dibaca_lewat_ajax(): void
    {
        // Inilah yang dipanggil `initNotificationBell()` saat panel lonceng
        // dibuka: POST + Accept JSON, bukan form biasa, supaya angkanya bisa
        // dihapus di tempat tanpa memuat ulang halaman (yang akan menutup
        // panel yang baru saja dibuka).
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->postJson(route('notifications.readAll'))
            ->assertOk()
            ->assertJson(['message' => __('notifications.mark_all_done')]);

        $this->assertSame(0, $member->notifications()->whereNull('read_at')->count());
    }

    public function test_angka_dan_penanda_belum_dibaca_hilang_setelah_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-badge', false)
            ->assertSee('<li data-notification-unread', false);

        $member->notifications()->update(['read_at' => now()]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-notification-badge', false)
            ->assertDontSee('<li data-notification-unread', false);
    }

    public function test_lonceng_tidak_tampil_untuk_tamu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('notifications.index'), false);
    }
}
