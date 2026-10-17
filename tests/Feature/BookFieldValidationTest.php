<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_year_cannot_be_in_the_future(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Masa Depan',
                'isbn' => '978-602-444-444-4',
                'publication_year' => (int) now()->year + 5,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertSessionHasErrors('publication_year');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Status',
                'isbn' => '978-602-555-555-5',
                'publication_year' => 2023,
                'status' => 'status-ngawur',
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertSessionHasErrors('status');
    }
}
