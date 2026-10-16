<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanReturned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    /**
     * Buku sedang dipinjam + pinjaman telat yang siap dikembalikan pustakawan.
     */
    private function overdueLoan(): Loan
    {
        return Loan::factory()->overdue()->create([
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'due_at' => now()->subDays(3),
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

    public function test_anggota_disanksi_tidak_bisa_meminjam_buku(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        // Jalur anggota: pesan penolakan dikirim sebagai toast `status`
        // (lihat LoanController::borrow) dengan batas akhir sanksi.
        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'sanksi'));

        // Tidak ada peminjaman yang tercatat — sanksi benar-benar menahan.
        $this->assertSame(0, Loan::query()->where('user_id', $member->id)->count());
    }

    public function test_pustakawan_juga_ditolak_mencatat_pinjaman_untuk_anggota_disanksi(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(0, Loan::query()->where('user_id', $member->id)->count());
    }

    public function test_sanksi_berakhir_otomatis_setelah_72_jam(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        // Lewati tepat satu jam setelah batas sanksi.
        $this->travelTo($member->suspended_until->copy()->addHour());

        $this->assertFalse($member->refresh()->isSuspended());

        $this->actingAs($member)
            ->post(route('books.borrow', $book))
            ->assertSessionHas('status');

        $this->assertSame(1, Loan::query()->where('user_id', $member->id)->count());
    }

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

    public function test_tombol_pinjam_nonaktif_selama_sanksi(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('books.borrow', $book), $content);
        $this->assertStringContainsString('masa sanksi', $content);
    }

    public function test_pengembalian_terlambat_mengirim_notifikasi_sanksi_ke_anggota(): void
    {
        $loan = $this->overdueLoan();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $notification = $loan->user->refresh()->notifications()
            ->where('type', LoanReturned::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertNotNull($notification->data['suspended_until']);
        $this->assertTrue(
            Carbon::parse($notification->data['suspended_until'])
                ->equalTo($loan->user->suspended_until),
        );
    }
}
