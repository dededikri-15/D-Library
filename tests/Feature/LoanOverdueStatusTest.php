<?php

namespace Tests\Feature;

use App\Actions\MarkOverdueLoans;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanOverdueStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_buku_terlambat_tetap_berstatus_borrowed(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDays(3),
        ]);

        app(MarkOverdueLoans::class)->handle();

        // Buku fisiknya masih di luar pustaka. Kalau jadi available, anggota
        // lain bisa langsung meminjam buku yang belum dikembalikan.
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }

    public function test_artisian_command_menandai_peminjaman_terlambat(): void
    {
        Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDay(),
        ]);

        $this->artisan('loans:mark-overdue')
            ->expectsOutputToContain('1 peminjaman')
            ->assertSuccessful();
    }

    public function test_scheduler_terjadwal_setiap_jam(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'loans:mark-overdue'));

        $this->assertCount(1, $events, 'Command overdue harus dijadwalkan.');
        $this->assertSame('0 * * * *', $events->first()->expression);
    }

    public function test_halaman_peminjaman_menampilkan_status_overdue_terbarui(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDays(2),
        ]);

        // Halaman harus menyegarkan statusnya sendiri, tanpa bergantung pada
        // scheduler yang mungkin tidak jalan di mesin(test.
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.index'))
            ->assertOk();

        $this->assertSame(Loan::STATUS_OVERDUE, $loan->fresh()->status);
    }
}
