<?php

namespace Tests\Feature;

use App\Actions\BorrowBook;
use App\Actions\MarkOverdueLoans;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoanOverdueCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tempo_peminjaman_bisa_diubah_lewat_config(): void
    {
        config(['perpustakaan.loan.duration_days' => 3]);

        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $borrowedAt = Carbon::parse('2026-01-10 09:00:00');

        $loan = app(BorrowBook::class)->handle(
            User::factory()->anggota()->create(),
            $book->id,
            $borrowedAt
        );

        $this->assertTrue($loan->due_at->isSameDay($borrowedAt->copy()->addDays(3)));
    }

    /*
    |--------------------------------------------------------------------------
    | 10.10 - Status overdue
    |--------------------------------------------------------------------------
    */

    public function test_peminjaman_lewat_tempo_berubah_jadi_overdue(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDay(),
        ]);

        $changed = app(MarkOverdueLoans::class)->handle();

        $this->assertSame(1, $changed);
        $this->assertSame(Loan::STATUS_OVERDUE, $loan->fresh()->status);
    }

    public function test_peminjaman_yang_belum_lewat_tempo_tidak_diubah(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->addDays(3),
        ]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
    }

    public function test_jatuh_tempo_tepat_hari_ini_belum_terlambat(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->addHours(6),
        ]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
    }

    public function test_peminjaman_yang_sudah_dikembalikan_tidak_jadi_overdue(): void
    {
        $loan = Loan::factory()->returned()->create(['due_at' => now()->subDays(10)]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_RETURNED, $loan->fresh()->status);
    }
}
