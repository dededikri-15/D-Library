<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur perpanjangan peminjaman dan denda keterlambatan.
 *
 * Aturan yang dijaga test ini:
 * - perpanjangan hanya untuk pinjaman aktif yang belum lewat tempo,
 * - dibatasi `config('perpustakaan.loan.max_renewals')`,
 * - anggota hanya untuk pinjamannya sendiri, pustakawan untuk semua,
 * - denda = hari terlambat × `fine_per_day`, di-snapshot saat pengembalian,
 * - penandaan "lunas" hanya untuk denda yang tercatat dan belum dibayar.
 */
class LoanRenewalAndFineTest extends TestCase
{
    use RefreshDatabase;

    private function memberLoan(array $attributes = []): Loan
    {
        return Loan::factory()->create($attributes + [
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now()->subDays(3),
            'due_at' => now()->addDays(11),
        ]);
    }

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

    public function test_pengembalian_terlambat_menyimpan_denda(): void
    {
        $loan = Loan::factory()->overdue()->create([
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'due_at' => now()->subDays(3),
        ]);
        $expectedFine = 3 * (int) config('perpustakaan.loan.fine_per_day');

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertSame(Loan::STATUS_RETURNED, $loan->status);
        $this->assertSame($expectedFine, $loan->fine);
        $this->assertTrue($loan->hasUnpaidFine());
    }

    public function test_pengembalian_tepat_waktu_tidak_berdenda(): void
    {
        $loan = $this->memberLoan();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $loan->refresh();
        $this->assertSame(0, $loan->fine);
        $this->assertFalse($loan->hasUnpaidFine());
    }

    public function test_pustakawan_bisa_menandai_denda_lunas(): void
    {
        $loan = Loan::factory()->returned()->create([
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'fine' => 5000,
            'fine_paid_at' => null,
        ]);
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->post(route('loans.fine.pay', $loan))
            ->assertSessionHas('status');

        $paidAt = $loan->fresh()->fine_paid_at;
        $this->assertNotNull($paidAt);

        // Kedua kalinya tidak ada yang bisa ditagih lagi.
        $this->actingAs($staff)
            ->post(route('loans.fine.pay', $loan))
            ->assertSessionHas('error');

        $this->assertTrue($loan->fresh()->fine_paid_at->equalTo($paidAt));
    }

    public function test_tombol_perpanjang_muncul_hanya_ketika_berhak(): void
    {
        $eligible = $this->memberLoan();
        $ineligible = $this->memberLoan([
            'user_id' => $eligible->user_id,
            'renew_count' => 1,
        ]);

        $content = $this->actingAs($eligible->user)
            ->get(route('loans.mine'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('loans.mine.renew', $eligible), $content);
        $this->assertStringNotContainsString(route('loans.mine.renew', $ineligible), $content);
    }

    public function test_pustakawan_melihat_denda_belum_lunas_di_daftar(): void
    {
        $loan = Loan::factory()->returned()->create([
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'fine' => 4000,
            'fine_paid_at' => null,
        ]);

        $content = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('4.000', $content);
        $this->assertStringContainsString(route('loans.fine.pay', $loan), $content);
    }
}
