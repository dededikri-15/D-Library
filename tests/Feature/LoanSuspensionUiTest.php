<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesRenewalFixtures;
use Tests\TestCase;

class LoanSuspensionUiTest extends TestCase
{
    use ProvidesRenewalFixtures;
    use RefreshDatabase;

    public function test_sanksi_kedua_pengembalian_telat_tidak_memendekkan(): void
    {
        $member = User::factory()->anggota()->create();
        $sharedDueAt = now()->subDays(5);
        $first = Loan::factory()->overdue()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'due_at' => $sharedDueAt,
        ]);
        $second = Loan::factory()->overdue()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'due_at' => $sharedDueAt,
        ]);
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)->post(route('loans.return', $first));
        $firstSuspension = $member->refresh()->suspended_until->copy();

        // Pengembalian kedua diproses 1 jam kemudian: batas sanksi mundur
        // (72 jam dari yang baru), tapi tidak pernah maju melewati yang
        // sudah ada.
        $this->travel(1)->hours();
        $this->actingAs($staff->refresh())->post(route('loans.return', $second));

        $this->assertTrue($member->refresh()->suspended_until->greaterThanOrEqualTo($firstSuspension));
        $this->assertEqualsWithDelta(
            now()->addHours((int) config('perpustakaan.loan.suspension_hours'))->timestamp,
            $member->suspended_until->timestamp,
            5,
        );
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

    public function test_pustakawan_melihat_masa_sanksi_anggota_di_daftar(): void
    {
        $loan = $this->overdueLoan();
        $this->actingAs($staff = User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan));

        $member = $loan->user->refresh();
        $this->assertTrue($member->isSuspended());

        $content = $this->actingAs($staff)
            ->get(route('loans.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sanksi meminjam s/d', $content);
    }

    public function test_anggota_melihat_banner_sanksi_di_riwayat(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        // Satu pinjaman supaya halaman tidak kosong — banner memang harus
        // tampil di atas tabel mana pun.
        Loan::factory()->returned()->create(['user_id' => $member->id]);

        $content = $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sanksi larangan meminjam', $content);
    }
}
