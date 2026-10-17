<?php

namespace Tests\Concerns;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;

/**
 * Fixture pinjaman untuk test perpanjangan & sanksi — dipakai bersama
 * oleh kelas hasil pemecahan LoanRenewalAndSuspensionTest.
 */
trait ProvidesRenewalFixtures
{
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
}
