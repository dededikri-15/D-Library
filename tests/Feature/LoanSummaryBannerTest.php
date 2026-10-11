<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanSummaryBannerTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->pustakawan()->create();
    }

    public function test_overdue_banner_appears_when_a_loan_is_late(): void
    {
        Loan::factory()->overdue()->create();

        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('1 peminjaman terlambat', false);
    }

    public function test_return_pending_banner_appears_when_a_member_requested_return(): void
    {
        Loan::factory()->create(['return_requested_at' => now()]);

        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('1 anggota mengajukan pengembalian', false);
    }

    public function test_due_soon_banner_appears_for_loans_nearing_deadline(): void
    {
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(13),
            'due_at' => now()->addDay(),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('1 peminjaman jatuh tempo dalam 3 hari', false);
    }

    public function test_active_count_banner_appears(): void
    {
        Loan::factory()->create([
            'borrowed_at' => now(),
            'due_at' => now()->addDays(14),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('1 buku sedang dipinjam', false);
    }

    public function test_clear_banner_appears_when_nothing_needs_attention(): void
    {
        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Tidak ada peminjaman yang perlu perhatian.', false);
    }

    public function test_returned_loans_are_not_counted_in_banner(): void
    {
        Loan::factory()->returned()->create();

        $this->actingAs($this->staff())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Tidak ada peminjaman yang perlu perhatian.', false)
            ->assertDontSee('peminjaman terlambat', false)
            ->assertDontSee('sedang dipinjam', false);
    }
}
