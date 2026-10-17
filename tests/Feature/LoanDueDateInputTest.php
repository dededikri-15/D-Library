<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanDueDateInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanggal_jatuh_tempo_diisi_otomatis_bila_tidak_disertakan(): void
    {
        // `borrowed_at` dan `due_at` NOT NULL di database. Tanpa hook
        // `creating` di model, setiap pemanggil yang lupa mengisinya gagal
        // dengan QueryException yang sulit dibaca.
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $loan = Loan::create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->assertNotNull($loan->borrowed_at);
        $this->assertSame(
            Loan::dueAt($loan->borrowed_at)->toDateString(),
            $loan->due_at->toDateString(),
        );
    }

    public function test_tanggal_jatuh_tempo_eksplisit_tidak_ditimpa(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();
        $custom = now()->addDays(99);

        $loan = Loan::create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => $custom,
        ]);

        $this->assertSame($custom->toDateString(), $loan->due_at->toDateString());
    }
}
