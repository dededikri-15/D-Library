<?php

namespace Tests\Concerns;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;

/**
 * Fixture pinjaman untuk test notifikasi peminjaman — dipakai bersama
 * oleh kelas hasil pemecahan LoanNotificationTest.
 */
trait ProvidesLoanFixture
{
    private function loanFor(User $member, array $attributes = []): Loan
    {
        return Loan::factory()->create($attributes + [
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);
    }
}
