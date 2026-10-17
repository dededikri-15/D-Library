<?php

namespace Tests\Feature;

use App\Notifications\LoanDueSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class NotificationBellUiTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    /**
     * Regresi plural (Task 26.4): `trans_choice` harus dikirim KUNCI, bukan
     * hasil `__()`. Kirim hasil `__()` → `Translator::localeForChoice()` tidak
     * mengenali kiriman itu sebagai kunci → locale jatuh ke `fallback_locale`
     * ('id') → aturan jamak Indonesia selalu memilih bentuk pertama, jadi
     * layar Inggris tampil "2 unread notification".
     *
     * Dua teks dicek terpisah karena keduanya memakai kunci berbeda:
     * lonceng memakai `bell_unread`, halaman memakai `unread_count`.
     */
    public function test_lonceng_dan_halaman_notifikasi_memakai_bentuk_jamak_bahasa_inggris(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $html = $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-notification-unread-text[^>]*>.*?2 unread notifications\b/su',
            $html,
        );

        $this->assertMatchesRegularExpression(
            '/text-tertiary uppercase">\s*2 unread notifications\s*<\/p>/su',
            $html,
        );
    }

    /**
     * Pasangan angka 1: bentuk tunggal harus tetap Inggris dan benar —
     * tanpa "s". Pola lama ikut merusak ini karena teksnya jatuh ke locale
     * 'id' (walau hurufnya kebetulan sama untuk kunci tanpa "|" di id).
     */
    public function test_lonceng_memakai_bentuk_tunggal_bahasa_inggris(): void
    {
        $member = $this->member();
        $member->notify(new LoanDueSoon($this->loanFor($member)));

        $html = $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-notification-unread-text[^>]*>.*?1 unread notification\b/su',
            $html,
        );
    }

    public function test_menandai_satu_notifikasi_membuka_tujuannya_lalu_menenandai_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $stored = $member->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $stored))
            ->assertRedirect(route('loans.mine'));

        $this->assertNotNull($stored->fresh()->read_at);
    }
}
