<?php

namespace Tests\Feature;

use App\Notifications\LoanDueSoon;
use App\Notifications\LoanOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class LoanOverdueNotificationTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    public function test_keterlambatan_diberitahukan_ke_anggota_dan_pustakawan_tanpa_dobel(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member, [
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        // Dua kali buka beranda = dua kali pengecekan keterlambatan.
        $this->get(route('home'))->assertOk();
        $this->get(route('home'))->assertOk();

        Notification::assertSentToTimes($member, LoanOverdue::class, 1);
        Notification::assertSentToTimes($staff, LoanOverdue::class, 1);
        $this->assertNotNull($loan->fresh()->overdue_alerted_at);

        $memberData = Notification::sent($member, LoanOverdue::class)->first()->toArray($member);
        $staffData = Notification::sent($staff, LoanOverdue::class)->first()->toArray($staff);

        $this->assertSame('overdue_member', $memberData['type']);
        $this->assertArrayNotHasKey('user_name', $memberData, 'Anggota tidak perlu diberi tahu namanya sendiri.');

        $this->assertSame('overdue_staff', $staffData['type']);
        $this->assertSame($member->name, $staffData['user_name']);
        $this->assertNotEmpty($staffData['borrowed_at']);
        $this->assertNotEmpty($staffData['due_at']);
        $this->assertGreaterThan(0, $staffData['overdue_days']);
    }

    public function test_pengingat_mendekati_jatuh_tempo_terkirim_tepat_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = $this->loanFor($member, ['due_at' => now()->addDay()]);

        $this->artisan('loans:remind')->assertSuccessful();
        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentToTimes($member, LoanDueSoon::class, 1);

        $data = Notification::sent($member, LoanDueSoon::class)->first()->toArray($member);
        $this->assertSame('due_soon', $data['type']);
        $this->assertArrayHasKey('renewals_left', $data);
    }

    public function test_halaman_notifikasi_menampilkan_daftar_dan_jumlah_belum_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.types.due_soon.title'))
            ->assertSee(trans_choice('notifications.unread_count', 1));
    }
}
