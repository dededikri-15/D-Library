<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationReturnTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_pengajuan_pengembalian_mengirim_jejak_aksi_ke_pelaku_anggota(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        $this->assertActionLogged($member, 'return_requested_self');
    }

    public function test_pengembalian_mengirim_jejak_aksi_ke_pustakawan(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($staff)->post(route('loans.return', $loan))->assertRedirect();
        $this->assertActionLogged($staff, 'return_recorded');
    }

    public function test_menghapus_catatan_peminjaman_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($staff)->delete(route('loans.destroy', $loan))->assertRedirect();

        $this->assertActionLogged($staff, 'loan_record_deleted');
    }
}
