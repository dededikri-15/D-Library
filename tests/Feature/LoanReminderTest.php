<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pengingat jatuh tempo lewat email (`loans:remind`).
 *
 * Aturan yang dijaga test ini:
 * - pengingat H-1/jatuh tempo terkirim sekali per peminjaman,
 * - pemberitahuan keterlambatan terkirim sekali, setelah status disegarkan,
 * - peminjaman yang sudah dikembalikan tidak dihitung,
 * - run berulang (jadwal harian, scheduler dobel) tidak menghasilkan email
 *   ganda — ini yang dijamin klaim atomik di SendLoanReminders.
 */
class LoanReminderTest extends TestCase
{
    use RefreshDatabase;

    private function activeLoan(array $attributes = []): Loan
    {
        return Loan::factory()->create($attributes + [
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now()->subDays(13),
            'due_at' => now()->addDay(),
        ]);
    }

    public function test_pengingat_terkirim_untuk_pinjaman_jatuh_tempo_besok(): void
    {
        Notification::fake();
        $loan = $this->activeLoan();

        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentTo(
            $loan->user,
            LoanDueReminder::class,
            fn (LoanDueReminder $notification) => $notification->kind === LoanDueReminder::KIND_DUE_SOON,
        );
        $this->assertNotNull($loan->fresh()->due_reminder_sent_at);
    }

    public function test_pinjaman_masih_lama_tidak_mendapat_pengingat(): void
    {
        Notification::fake();
        $loan = $this->activeLoan(['due_at' => now()->addDays(3)]);

        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertNotSentTo($loan->user, LoanDueReminder::class);
        $this->assertNull($loan->fresh()->due_reminder_sent_at);
    }

    public function test_pinjaman_sudah_dikembalikan_tidak_mendapat_pengingat(): void
    {
        Notification::fake();
        $loan = $this->activeLoan([
            'status' => Loan::STATUS_RETURNED,
            'returned_at' => now(),
        ]);

        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertNotSentTo($loan->user, LoanDueReminder::class);
    }

    public function test_pengingat_tidak_terkirim_dobel(): void
    {
        Notification::fake();
        $loan = $this->activeLoan();

        $this->artisan('loans:remind')->assertSuccessful();
        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentToTimes($loan->user, LoanDueReminder::class, 1);
    }

    public function test_keterlambatan_didahului_penyegaran_status_lalu_diberitahukan(): void
    {
        Notification::fake();
        // Status masih `borrowed` padahal due_at sudah lewat 2 hari —
        // persis keadaan sebelum scheduler `loans:mark-overdue` sempat jalan.
        $loan = $this->activeLoan(['due_at' => now()->subDays(2)]);

        $this->artisan('loans:remind')->assertSuccessful();

        $loan->refresh();
        $this->assertSame(Loan::STATUS_OVERDUE, $loan->status);
        Notification::assertSentTo(
            $loan->user,
            LoanDueReminder::class,
            fn (LoanDueReminder $notification) => $notification->kind === LoanDueReminder::KIND_OVERDUE,
        );
        $this->assertNotNull($loan->overdue_notified_at);
    }

    public function test_pemberitahuan_keterlambatan_tidak_terkirim_dobel(): void
    {
        Notification::fake();
        $loan = $this->activeLoan(['due_at' => now()->subDays(2)]);

        $this->artisan('loans:remind')->assertSuccessful();
        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentToTimes($loan->user, LoanDueReminder::class, 1);
    }

    public function test_email_benar_benar_masuk_dengan_subjek_yang_berisi_judul(): void
    {
        // Tanpa Notification::fake — biarkan mailer database bekerja,
        // membuktikan view email ter-render tanpa error dan subjeknya
        // memuat judul buku. phpunit.xml memaksa MAIL_MAILER=array (yang
        // membuang email ke memori), jadi untuk test ini dikembalikan ke
        // transport database.
        config(['mail.default' => 'database']);
        $loan = $this->activeLoan(['book_id' => Book::factory()->create(['title' => 'Buku Uji Pengingat'])]);

        $this->artisan('loans:remind')->assertSuccessful();

        $this->assertDatabaseHas('mail_messages', [
            'to_address' => $loan->user->email,
        ]);
        $this->assertTrue(
            \DB::table('mail_messages')->where('subject', 'like', '%Buku Uji Pengingat%')->exists(),
        );
    }
}
