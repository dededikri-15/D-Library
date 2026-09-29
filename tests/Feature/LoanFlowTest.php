<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_loan_list(): void
    {
        $loan = Loan::factory()->create();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee($loan->book->title);
    }

    public function test_anggota_cannot_view_loan_list(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('loans.index'))
            ->assertForbidden();
    }

    public function test_staff_can_record_loan_and_due_date_is_calculated(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $member = User::factory()->anggota()->create();
        $duration = config('perpustakaan.loan.duration_days');

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $loan = Loan::where('book_id', $book->id)->firstOrFail();

        $this->assertSame(Loan::STATUS_BORROWED, $loan->status);
        $this->assertTrue($loan->borrowed_at->isSameDay(now()));
        $this->assertTrue($loan->due_at->isSameDay(now()->addDays($duration)));
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }

    public function test_staff_can_record_loan_for_today_in_display_timezone(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $member = User::factory()->anggota()->create();
        $today = now(config('perpustakaan.display_timezone'))->toDateString();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => $today,
            ])
            ->assertRedirect();

        $loan = Loan::where('book_id', $book->id)->firstOrFail();

        $this->assertSame($today, $loan->displayDate($loan->borrowed_at)?->toDateString());
    }

    public function test_book_already_on_loan_cannot_be_loaned_again(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => User::factory()->anggota()->create()->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(1, Loan::where('book_id', $book->id)->count());
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }

    public function test_overdue_loan_also_blocks_new_loan(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->overdue()->create(['book_id' => $book->id]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => User::factory()->anggota()->create()->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('book_id');
    }

    public function test_loan_date_cannot_be_in_the_future(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => User::factory()->anggota()->create()->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->addWeek()->toDateString(),
            ])
            ->assertSessionHasErrors('borrowed_at');
    }

    public function test_returning_book_marks_loan_returned_and_book_available(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        $loan->refresh();
        $this->assertSame(Loan::STATUS_RETURNED, $loan->status);
        $this->assertNotNull($loan->returned_at);
        $this->assertSame(Book::STATUS_AVAILABLE, $book->fresh()->status);
    }

    public function test_pustakawan_can_confirm_member_return_request(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'return_requested_at' => now()->subMinute(),
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        $this->assertSame(Loan::STATUS_RETURNED, $loan->fresh()->status);
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertNotNull($loan->fresh()->return_requested_at);
        $this->assertSame(Book::STATUS_AVAILABLE, $book->fresh()->status);
    }

    public function test_pustakawan_sees_confirmation_button_for_return_request(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'return_requested_at' => now(),
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Konfirmasi pengembalian');
    }

    public function test_returning_twice_is_rejected(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $loan = Loan::factory()->returned()->create(['book_id' => $book->id]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        $this->assertSame(Loan::STATUS_RETURNED, $loan->fresh()->status);
    }

    public function test_deleting_active_loan_frees_the_book(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect();

        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
        $this->assertSame(Book::STATUS_AVAILABLE, $book->fresh()->status);
    }

    public function test_anggota_cannot_record_loans(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs(User::factory()->anggota()->create())
            ->post(route('loans.store'), [
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'borrowed_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('loans', 0);
    }
}
