<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Notifications\LoanApproved;
use App\Notifications\LoanRejected;
use App\Notifications\LoanReturned;
use App\Notifications\LoanReturnRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesLoanFixture;
use Tests\TestCase;

class NotificationBookTitleQuoteTest extends TestCase
{
    use CreatesLibraryUsers;
    use ProvidesLoanFixture;
    use RefreshDatabase;

    /**
     * Regresi tampilan: judul "Pahlawan yang Terlupakan" sekilas mirip kata
     * "pustakawan" — kalimatnya sekarang diawali kata "Buku" supaya tidak
     * terbaca sebagai orang. Judul buku di badan notif wajib dibungkus
     * kutip (“...” di id).
     */
    public function test_judul_buku_di_notif_diawali_kata_buku_dan_dikutip(): void
    {
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member, [
            'book_id' => Book::factory()->create([
                'title' => 'Pahlawan yang Terlupakan',
                'status' => Book::STATUS_BORROWED,
            ]),
        ]);
        $member->notify(new LoanReturned($loan->fresh('book'), $staff->name));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Buku “Pahlawan yang Terlupakan” yang Anda kembalikan', false);
    }

    /**
     * Nama orang JANGAN ikut berkutip — hanya judul buku yang dikutip,
     * supaya "Dede Dikri" tidak tampil sebagai “Dede Dikri”.
     */
    public function test_nama_pengaju_tidak_dibungkus_tanda_kutip(): void
    {
        $member = $this->member();
        $member->update(['name' => 'Dede Dikri']);
        $staff = $this->librarian();
        $loan = $this->loanFor($member);

        // Notif pengajuan (return_requested) dikirim KE pustakawan,
        // jadi dirender sebagai pustakawan tersebut.
        $staff->notify(new LoanReturnRequested($loan->fresh(['book', 'user'])));

        $html = $this->actingAs($staff)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Anggota Dede Dikri mengajukan pengembalian buku', $html);
        $this->assertStringNotContainsString('“Dede Dikri”', $html);
    }

    /**
     * Notif pengembalian menyebut nama pustakawan yang mengonfirmasi,
     * bukan cuma judul buku seperti sebelumnya.
     */
    public function test_notif_pengembalian_menyebut_nama_pustakawan(): void
    {
        $member = $this->member();
        $staff = $this->librarian();
        $staff->update(['name' => 'Sinta Pustaka']);
        $loan = $this->loanFor($member);

        $member->notify(new LoanReturned($loan->fresh('book'), $staff->name));

        $html = $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('telah dikonfirmasi pustakawan Sinta Pustaka', $html);
    }

    /**
     * Notif persetujuan menyebut nama pustakawan yang mencatat.
     */
    public function test_notif_persetujuan_menyebut_nama_pustakawan(): void
    {
        $member = $this->member();
        $staff = $this->librarian();
        $staff->update(['name' => 'Sinta Pustaka']);
        $loan = $this->loanFor($member);

        $member->notify(new LoanApproved($loan->fresh('book'), $staff->name));

        $html = $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('disetujui pustakawan Sinta Pustaka', $html);
    }

    /**
     * Notif pembatalan menyebut nama pustakawan yang membatalkan.
     */
    public function test_notif_pembatalan_menyebut_nama_pustakawan(): void
    {
        $member = $this->member();
        $staff = $this->librarian();
        $staff->update(['name' => 'Sinta Pustaka']);
        $loan = $this->loanFor($member);

        $member->notify(new LoanRejected($loan->fresh('book'), 'admin_deleted', $staff->name));

        $html = $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('dibatalkan oleh pustakawan Sinta Pustaka', $html);
    }
}
