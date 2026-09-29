<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_belongs_to_category_author_and_publisher(): void
    {
        $book = Book::factory()->create();

        $this->assertInstanceOf(Category::class, $book->category);
        $this->assertInstanceOf(Author::class, $book->author);
        $this->assertInstanceOf(Publisher::class, $book->publisher);
    }

    public function test_category_author_and_publisher_have_many_books(): void
    {
        $category = Category::factory()->create();
        $author = Author::factory()->create();
        $publisher = Publisher::factory()->create();

        Book::factory()->count(3)->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'publisher_id' => $publisher->id,
        ]);

        $this->assertCount(3, $category->books);
        $this->assertCount(3, $author->books);
        $this->assertCount(3, $publisher->books);
        $this->assertCount(3, $category->books()->pluck('id'));
    }

    public function test_user_has_many_loans_favorites_and_reading_histories(): void
    {
        $user = User::factory()->anggota()->create();

        Loan::factory()->count(2)->create(['user_id' => $user->id]);
        Favorite::factory()->count(2)->create(['user_id' => $user->id]);
        ReadingHistory::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->loans);
        $this->assertCount(2, $user->favorites);
        $this->assertCount(2, $user->readingHistories);
    }

    public function test_loan_favorite_and_reading_history_belong_to_user_and_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $loan = Loan::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
        $favorite = Favorite::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
        $history = ReadingHistory::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->assertTrue($loan->user->is($user));
        $this->assertTrue($loan->book->is($book));
        $this->assertTrue($favorite->user->is($user));
        $this->assertTrue($favorite->book->is($book));
        $this->assertTrue($history->user->is($user));
        $this->assertTrue($history->book->is($book));
    }

    public function test_book_has_many_loans_favorites_and_reading_histories(): void
    {
        $book = Book::factory()->create();

        Loan::factory()->count(2)->create(['book_id' => $book->id]);
        Favorite::factory()->count(2)->create(['book_id' => $book->id]);
        ReadingHistory::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->loans);
        $this->assertCount(2, $book->favorites);
        $this->assertCount(2, $book->readingHistories);
    }

    public function test_active_loans_scope_excludes_returned_loans(): void
    {
        $user = User::factory()->create();

        Loan::factory()->create(['user_id' => $user->id, 'status' => Loan::STATUS_BORROWED]);
        Loan::factory()->returned()->create(['user_id' => $user->id]);
        Loan::factory()->overdue()->create(['user_id' => $user->id]);

        $this->assertSame(3, $user->loans()->count());
        $this->assertSame(2, $user->activeLoans()->count());
        $this->assertSame(1, Loan::query()->overdue()->count());
    }

    public function test_book_available_scope_and_status_helpers(): void
    {
        Book::factory()->available()->count(2)->create();
        Book::factory()->borrowed()->create();
        Book::factory()->inactive()->create();

        $this->assertSame(2, Book::query()->available()->count());
        $this->assertSame(4, Book::count());

        $available = Book::query()->available()->first();
        $borrowed = Book::query()->where('status', Book::STATUS_BORROWED)->first();

        $this->assertTrue($available->isAvailable());
        $this->assertFalse($borrowed->isAvailable());
    }

    public function test_user_role_helpers(): void
    {
        $pustakawan = User::factory()->pustakawan()->create();
        $anggota = User::factory()->anggota()->create();

        $this->assertTrue($pustakawan->isPustakawan());
        $this->assertTrue($pustakawan->isStaff());

        $this->assertTrue($anggota->isAnggota());
        $this->assertFalse($anggota->isStaff());

        $this->assertSame(['pustakawan', 'anggota'], User::roles());
        $this->assertSame(['available', 'borrowed', 'inactive'], Book::statuses());
        $this->assertSame(['borrowed', 'returned', 'overdue'], Loan::statuses());
    }

    public function test_role_defaults_to_anggota_on_new_user(): void
    {
        $this->assertSame(User::ROLE_ANGGOTA, (new User)->forceFill([])->getAttribute('role') ?? User::ROLE_ANGGOTA);
        $this->assertSame(User::ROLE_ANGGOTA, User::factory()->create()->role);
    }

    public function test_casts_convert_columns_to_expected_types(): void
    {
        $book = Book::factory()->create(['publication_year' => 2021, 'pages' => 320]);
        $loan = Loan::factory()->create(['borrowed_at' => now(), 'due_at' => now()->addDays(14)]);
        $history = ReadingHistory::factory()->create(['last_page' => 42]);

        $this->assertIsInt($book->publication_year);
        $this->assertIsInt($book->pages);
        $this->assertInstanceOf(Carbon::class, $loan->borrowed_at);
        $this->assertInstanceOf(Carbon::class, $loan->due_at);
        $this->assertNull($loan->returned_at);
        $this->assertIsInt($history->last_page);
        $this->assertInstanceOf(Carbon::class, $history->last_read_at);
    }

    public function test_statuses_and_roles_are_stored_as_plain_strings(): void
    {
        $book = Book::factory()->borrowed()->create();

        $this->assertIsString($book->status);
        $this->assertSame('borrowed', $book->getRawOriginal('status'));
    }

    public function test_favorite_and_reading_history_reject_duplicate_user_book_pair(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Favorite::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
        ReadingHistory::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->expectException(QueryException::class);

        Favorite::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_deleting_a_book_cascades_to_related_rows(): void
    {
        $book = Book::factory()->create();

        Loan::factory()->create(['book_id' => $book->id]);
        Favorite::factory()->create(['book_id' => $book->id]);
        ReadingHistory::factory()->create(['book_id' => $book->id]);

        $book->delete();

        $this->assertSame(0, Loan::where('book_id', $book->id)->count());
        $this->assertSame(0, Favorite::where('book_id', $book->id)->count());
        $this->assertSame(0, ReadingHistory::where('book_id', $book->id)->count());
    }

    public function test_password_is_hashed_and_hidden(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(password_verify('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }
}
