<?php

namespace Tests\Feature;

use App\Models\Book;
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
     * "pustakawan" — tanpa tanda kutip, kalimat "Pahlawan yang Terlupakan
     * dikembalikan pada ..." bisa kebaca "pustakawan yang terlupakan".
     * Judul buku di badan notif wajib dibungkus kutip (“...” di id).
     */
    public function test_judul_buku_di_notif_dibungkus_tanda_kutip(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member, [
            'book_id' => Book::factory()->create([
                'title' => 'Pahlawan yang Terlupakan',
                'status' => Book::STATUS_BORROWED,
            ]),
        ]);
        $member->notify(new LoanReturned($loan->fresh('book')));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('“Pahlawan yang Terlupakan” dikembalikan pada', false);
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

        $this->assertStringContainsString('Dari anggota Dede Dikri untuk pustakawan:', $html);
        $this->assertStringNotContainsString('“Dede Dikri”', $html);
    }
}
