<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesRenewalFixtures;

class LoanRenewalAndReturnTest extends TestCase
{
    use RefreshDatabase;
    use ProvidesRenewalFixtures;

    public function test_pustakawan_bisa_memperpanjang_pinjaman_anggota(): void
    {
        $loan = $this->memberLoan();
        $oldDueAt = $loan->due_at->copy();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.renew', $loan))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertSame(1, $loan->renew_count);
        $this->assertTrue($loan->due_at->equalTo($oldDueAt->addDays(14)));
    }

    public function test_pengembalian_terlambat_menjatuhkan_sanksi_72_jam(): void
    {
        $loan = $this->overdueLoan();
        $hours = (int) config('perpustakaan.loan.suspension_hours');

        $this->travel(1)->hours();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertSame(Loan::STATUS_RETURNED, $loan->status);
        $this->assertTrue($loan->isLateReturn());

        $member = $loan->user->refresh();
        $this->assertTrue($member->isSuspended());
        $this->assertEqualsWithDelta(
            now()->addHours($hours)->timestamp,
            $member->suspended_until->timestamp,
            5,
        );
    }

    public function test_pengembalian_tepat_waktu_tidak_disanksi(): void
    {
        $loan = $this->memberLoan();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertFalse($loan->isLateReturn());
        $this->assertNull($loan->user->refresh()->suspended_until);
        $this->assertFalse($loan->user->isSuspended());
    }
}
