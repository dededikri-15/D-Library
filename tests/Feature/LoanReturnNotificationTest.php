<?php

namespace Tests\Feature;

use App\Notifications\LoanRejected;
use App\Notifications\LoanReturned;
use App\Notifications\LoanReturnRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class LoanReturnNotificationTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    public function test_pengajuan_pengembalian_hanya_memberi_tahu_pustakawan_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $librarian = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        Notification::assertSentTo($librarian, LoanReturnRequested::class);
        Notification::assertNotSentTo($member, LoanReturnRequested::class);

        // Pengajuan kedua masih "pending" — kabarnya sudah pernah dikirim.
        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        Notification::assertSentToTimes($librarian, LoanReturnRequested::class, 1);
    }

    public function test_pengembalian_oleh_pustakawan_mengabari_anggota_dengan_tanggal(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($staff)
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        Notification::assertSentTo($member, LoanReturned::class);

        $data = Notification::sent($member, LoanReturned::class)->first()->toArray($member);

        $this->assertSame('returned', $data['type']);
        $this->assertNotEmpty($data['returned_at'], 'Payload harus membawa tanggal pengembalian yang sudah di-refresh.');
    }

    public function test_penghapusan_peminjaman_mengabari_anggota_dengan_alasan_kode(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($staff)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect();

        Notification::assertSentTo(
            $member,
            LoanRejected::class,
            fn (LoanRejected $notification) => $notification->reason === LoanRejected::REASON_ADMIN_DELETED,
        );

        $data = Notification::sent($member, LoanRejected::class)->first()->toArray($member);

        // Kode, bukan kalimat — teksnya baru diterjemahkan saat dibaca.
        $this->assertSame('admin_deleted', $data['reason']);
    }
}
