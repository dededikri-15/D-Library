<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anggota = User::where('role', User::ROLE_ANGGOTA)->orderBy('id')->get();
        $books = Book::orderBy('id')->get();

        if ($anggota->isEmpty() || $books->isEmpty()) {
            return;
        }

        $active = [
            ['user' => 0, 'book' => 0, 'borrowed_days_ago' => 3, 'due_in_days' => 11],
            ['user' => 0, 'book' => 4, 'borrowed_days_ago' => 6, 'due_in_days' => 8],
            ['user' => 1, 'book' => 7, 'borrowed_days_ago' => 2, 'due_in_days' => 12],
            ['user' => 2, 'book' => 12, 'borrowed_days_ago' => 5, 'due_in_days' => 9],
        ];

        $overdue = [
            ['user' => 1, 'book' => 1, 'borrowed_days_ago' => 25, 'due_days_ago' => 11],
            ['user' => 2, 'book' => 15, 'borrowed_days_ago' => 30, 'due_days_ago' => 16],
        ];

        $returned = [
            ['user' => 0, 'book' => 2, 'borrowed_days_ago' => 20, 'returned_days_ago' => 10],
            ['user' => 1, 'book' => 5, 'borrowed_days_ago' => 18, 'returned_days_ago' => 9],
            ['user' => 2, 'book' => 9, 'borrowed_days_ago' => 15, 'returned_days_ago' => 5],
        ];

        foreach ($active as $loan) {
            $book = $books[$loan['book']];

            Loan::updateOrCreate(
                ['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED],
                [
                    'user_id' => $anggota[$loan['user']]->id,
                    'borrowed_at' => now()->subDays($loan['borrowed_days_ago']),
                    'due_at' => now()->addDays($loan['due_in_days']),
                    'returned_at' => null,
                ]
            );

            $book->update(['status' => Book::STATUS_BORROWED]);
        }

        foreach ($overdue as $loan) {
            $book = $books[$loan['book']];

            Loan::updateOrCreate(
                ['book_id' => $book->id, 'status' => Loan::STATUS_OVERDUE],
                [
                    'user_id' => $anggota[$loan['user']]->id,
                    'borrowed_at' => now()->subDays($loan['borrowed_days_ago']),
                    'due_at' => now()->subDays($loan['due_days_ago']),
                    'returned_at' => null,
                ]
            );

            $book->update(['status' => Book::STATUS_BORROWED]);
        }

        foreach ($returned as $loan) {
            $book = $books[$loan['book']];

            Loan::updateOrCreate(
                ['book_id' => $book->id, 'status' => Loan::STATUS_RETURNED],
                [
                    'user_id' => $anggota[$loan['user']]->id,
                    'borrowed_at' => now()->subDays($loan['borrowed_days_ago']),
                    'due_at' => now()->subDays($loan['borrowed_days_ago'] - config('perpustakaan.loan.duration_days')),
                    'returned_at' => now()->subDays($loan['returned_days_ago']),
                ]
            );
        }

        $favorites = [
            ['user' => 0, 'book' => 3],
            ['user' => 0, 'book' => 6],
            ['user' => 1, 'book' => 0],
            ['user' => 1, 'book' => 10],
            ['user' => 2, 'book' => 4],
            ['user' => 2, 'book' => 14],
        ];

        foreach ($favorites as $favorite) {
            Favorite::updateOrCreate(
                ['user_id' => $anggota[$favorite['user']]->id, 'book_id' => $books[$favorite['book']]->id],
                []
            );
        }

        $histories = [
            ['user' => 0, 'book' => 0, 'last_page' => 45, 'read_days_ago' => 1],
            ['user' => 0, 'book' => 4, 'last_page' => 120, 'read_days_ago' => 3],
            ['user' => 1, 'book' => 7, 'last_page' => 78, 'read_days_ago' => 2],
            ['user' => 1, 'book' => 1, 'last_page' => 30, 'read_days_ago' => 8],
            ['user' => 2, 'book' => 12, 'last_page' => 200, 'read_days_ago' => 5],
        ];

        foreach ($histories as $history) {
            ReadingHistory::updateOrCreate(
                ['user_id' => $anggota[$history['user']]->id, 'book_id' => $books[$history['book']]->id],
                [
                    'last_page' => $history['last_page'],
                    'last_read_at' => now()->subDays($history['read_days_ago']),
                ]
            );
        }
    }
}
