<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityFilterInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    /* ------------------------------------------------------------------
     | Task 7.2 — validasi input
     * ----------------------------------------------------------------- */

    public function test_non_numeric_loan_user_filter_does_not_crash_postgresql(): void
    {
        // `user_id` menuju kolom bigint. Tanpa validasi, PostgreSQL melempar
        // QueryException -> HTTP 500. Di SQLite test ini selalu lolos.
        $staff = User::factory()->pustakawan()->create();
        $first = User::factory()->anggota()->create();
        $second = User::factory()->anggota()->create();

        Loan::factory()->create(['user_id' => $first->id]);
        Loan::factory()->create(['user_id' => $second->id]);

        // Filter buruk diabaikan, jadi semua peminjaman tetap tampil.
        $this->actingAs($staff)
            ->get('/peminjaman?user_id=abc')
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 2);

        // Filter yang benar tetap bekerja.
        $this->actingAs($staff)
            ->get('/peminjaman?user_id='.$first->id)
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 1);
    }

    public function test_invalid_loan_status_filter_is_ignored_not_fatal(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get('/peminjaman?status=ngawur')
            ->assertOk();
    }

    public function test_invalid_book_status_filter_is_ignored_not_fatal(): void
    {
        $this->get('/buku?status=ngawur')->assertOk();
    }

    public function test_search_wildcards_are_treated_as_literal_characters(): void
    {
        Book::factory()->count(2)->create();

        // Tanpa escape LIKE, `%` akan membuat semua baris cocok.
        $this->get('/buku?q=%25')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->total() === 0);
    }

    public function test_overlong_search_term_is_ignored(): void
    {
        $this->get('/buku?q='.str_repeat('a', 500))->assertOk();
    }
}
