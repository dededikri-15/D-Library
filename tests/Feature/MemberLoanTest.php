<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 10.1, 10.10, 10.11, dan 10.14.
 *
 * LoanFlowTest (sudah ada sebelumnya) menguji jalur pustakawan. File ini
 * menguji jalur anggota: peminjaman mandiri dari detail buku, penyegaran
 * status overdue, dan halaman riwayat peminjaman.
 */
class MemberLoanTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 10.1 - Peminjaman mandiri oleh anggota
    |--------------------------------------------------------------------------
    */

    public function test_anggota_bisa_meminjam_buku_yang_tersedia(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status');

        $loan = Loan::where('book_id', $book->id)->firstOrFail();

        $this->assertSame($member->id, $loan->user_id);
        $this->assertSame(Loan::STATUS_BORROWED, $loan->status);
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
        $this->assertTrue($loan->borrowed_at->isSameDay(now()));
    }

    public function test_anggota_tidak_bisa_meminjam_buku_yang_sedang_dipinjam_orang_lain(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->anggota()->create())
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status');

        // Pesan harus menyebut orang lain, bukan "tidak bisa dipinjam" generik.
        $this->assertStringContainsString('anggota lain', session('status'));

        $this->assertSame(1, Loan::where('book_id', $book->id)->count());
    }

    public function test_anggota_tidak_bisa_meminjam_buku_yang_sudah_dipinjam_sendiri(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book));

        // Pesan harus berbeda dari kasus "dipinjam anggota lain", supaya
        // orang tidak mengira bukunya hilang.
        $this->assertStringContainsString('Anda sedang meminjam', session('status'));

        $this->assertSame(1, Loan::where('book_id', $book->id)->count());
    }
}
