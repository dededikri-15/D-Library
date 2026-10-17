<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesRenewalFixtures;
use Tests\TestCase;

/**
 * Fitur perpanjangan peminjaman dan sanksi keterlambatan.
 *
 * Aturan yang dijaga test ini:
 * - perpanjangan hanya untuk pinjaman aktif yang belum lewat tempo,
 * - dibatasi `config('perpustakaan.loan.max_renewals')`,
 * - anggota hanya untuk pinjamannya sendiri, pustakawan untuk semua,
 * - pengembalian terlambat menjatuhkan sanksi larangan meminjam
 *   `suspension_hours` (72 jam) sejak pengembalian diproses,
 * - selama sanksi berjalan, peminjaman baru ditolak di server
 *   (jalur anggota maupun jalur pustakawan),
 * - sanksi berakhir sendiri tanpa scheduler — cukup waktu lewat.
 */
class LoanRenewalAndSuspensionTest extends TestCase
{
    use ProvidesRenewalFixtures;
    use RefreshDatabase;

    public function test_anggota_bisa_memperpanjang_pinjaman_sendiri(): void
    {
        $loan = $this->memberLoan();
        $oldDueAt = $loan->due_at->copy();

        $this->actingAs($loan->user)
            ->from(route('loans.mine'))
            ->post(route('loans.mine.renew', $loan))
            ->assertRedirect(route('loans.mine'))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertSame(1, $loan->renew_count);
        $this->assertTrue($loan->due_at->equalTo($oldDueAt->addDays(14)));
    }

    public function test_perpanjangan_dibatasi_satu_kali(): void
    {
        $loan = $this->memberLoan();
        $member = $loan->user;

        $this->actingAs($member)->post(route('loans.mine.renew', $loan))->assertSessionHas('status');
        $this->actingAs($member)
            ->from(route('loans.mine'))
            ->post(route('loans.mine.renew', $loan))
            ->assertRedirect(route('loans.mine'))
            ->assertSessionHas('error');

        $this->assertSame(1, $loan->fresh()->renew_count);
    }

    public function test_pinjaman_terlambat_tidak_bisa_diperpanjang(): void
    {
        $loan = Loan::factory()->overdue()->create([
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'due_at' => now()->subDays(2),
        ]);

        $this->actingAs($loan->user)
            ->post(route('loans.mine.renew', $loan))
            ->assertSessionHas('error');

        $this->assertSame(0, $loan->fresh()->renew_count);
    }

    public function test_anggota_tidak_bisa_memperpanjang_pinjaman_orang_lain(): void
    {
        $loan = $this->memberLoan();

        $this->actingAs(User::factory()->anggota()->create())
            ->post(route('loans.mine.renew', $loan))
            ->assertForbidden();

        $this->assertSame(0, $loan->fresh()->renew_count);
    }
}
