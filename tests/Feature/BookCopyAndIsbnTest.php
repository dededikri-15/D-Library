<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookCopyAndIsbnTest extends TestCase
{
    use RefreshDatabase;

    public function test_added_copies_follow_inactive_book_status(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'status' => Book::STATUS_INACTIVE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'add_copies' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(2, $book->fresh()->total_copies);
        $this->assertSame(0, $book->availableCopies()->count());
    }

    public function test_book_create_rejects_duplicate_isbn(): void
    {
        $existing = Book::factory()->create(['isbn' => '978-602-222-222-2']);
        $new = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Kembar',
                'isbn' => '978-602-222-222-2',
                'publication_year' => 2023,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $new->category_id,
                'author_id' => $new->author_id,
                'publisher_id' => $new->publisher_id,
            ])
            ->assertSessionHasErrors('isbn');

        $this->assertSame(1, Book::where('isbn', $existing->isbn)->count());
    }

    public function test_book_create_allows_keeping_own_isbn_when_editing(): void
    {
        $book = Book::factory()->create(['isbn' => '978-602-333-333-3']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => 'Judul Baru',
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'pages' => $book->pages,
                'status' => $book->status,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertRedirect();

        $this->assertSame('Judul Baru', $book->fresh()->title);
    }
}
