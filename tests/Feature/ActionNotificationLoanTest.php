<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationLoanTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_simpan_posisi_baca_mengirim_jejak_aksi_dan_hapus_riwayat_juga(): void
    {
        Notification::fake();
        $member = $this->member();
        $book = Book::factory()->create([
            'status' => Book::STATUS_BORROWED,
            'file' => 'books/uji.pdf',
        ]);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)->post(route('reading-histories.store'), [
            'book_id' => $book->id,
            'last_page' => 12,
        ])->assertRedirect();
        $this->assertActionLogged($member, 'reading_saved');

        $history = ReadingHistory::where('user_id', $member->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->actingAs($member)->delete(route('reading-histories.destroy', $history))
            ->assertRedirect();
        $this->assertActionLogged($member, 'reading_removed');
    }

    /* ------------------------------------------------------------------
     | Peminjaman: celah yang belum punya kabar untuk pelaku
     * ----------------------------------------------------------------- */

    public function test_pustakawan_mencatat_peminjaman_mendapat_jejak_aksi(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs($staff)->post(route('loans.store'), [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'loan_recorded');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame($book->title, $payload['subject']);
        $this->assertSame($member->name, $payload['user_name']);
    }

    public function test_perpanjangan_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->post(route('loans.mine.renew', $loan))
            ->assertRedirect();

        $this->assertActionLogged($member, 'loan_renewed');
    }
}
