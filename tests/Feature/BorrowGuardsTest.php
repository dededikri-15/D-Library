<?php

namespace Tests\Feature;

use App\Actions\BorrowBook;
use App\Exceptions\LoanNotPossibleException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BorrowGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_pinjam_menghilang_saat_anggota_meminjam_buku_yang_sama(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('sedang meminjam buku ini')
            ->assertDontSee('action="'.route('books.borrow', $book).'"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | 10.13 - Peminjaman ganda, diuji langsung di action
    |--------------------------------------------------------------------------
    |
    | Yang diuji di sini bukan HTTP response, tapi aturan bisnisnya, karena
    | dua jalur peminjaman (anggota & staff) sama-sama melewati BorrowBook.
    |
    */

    public function test_borrow_book_menolak_buku_yang_sudah_dipinjam(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $first = User::factory()->anggota()->create();
        $second = User::factory()->anggota()->create();

        app(BorrowBook::class)->handle($first, $book->id);

        $this->expectException(LoanNotPossibleException::class);
        app(BorrowBook::class)->handle($second, $book->id);
    }

    public function test_borrow_book_melempar_untuk_buku_yang_tidak_ada(): void
    {
        $this->expectException(LoanNotPossibleException::class);

        app(BorrowBook::class)->handle(User::factory()->anggota()->create(), 999999);
    }

    public function test_jatuh_tempo_dihitung_dari_config(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $borrowedAt = Carbon::parse('2026-01-10 09:00:00');

        $loan = app(BorrowBook::class)->handle(
            User::factory()->anggota()->create(),
            $book->id,
            $borrowedAt
        );

        $this->assertTrue($loan->due_at->isSameDay($borrowedAt->copy()->addDays(14)));
    }
}
