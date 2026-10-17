<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_book_create_form(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('Tambah Buku');
    }

    public function test_anggota_cannot_open_book_create_form(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.create'))
            ->assertForbidden();
    }

    public function test_pustakawan_can_create_book(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => $book->title,
                'isbn' => '978-602-111-111-1',
                'description' => 'Deskripsi uji.',
                'publication_year' => 2023,
                'pages' => 250,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('books', ['isbn' => '978-602-111-111-1', 'title' => $book->title]);
    }

    public function test_staff_can_create_book_with_multiple_inventory_copies(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => $book->title,
                'isbn' => '978-602-111-112-8',
                'publication_year' => 2023,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'initial_copies' => 3,
            ])
            ->assertRedirect();

        $createdBook = Book::where('isbn', '978-602-111-112-8')->firstOrFail();

        $this->assertSame(3, $createdBook->copies()->count());
        $this->assertSame(3, $createdBook->copies()->distinct('inventory_code')->count());
        $this->assertSame(3, $createdBook->total_copies);
    }

    public function test_staff_can_add_inventory_copies_to_existing_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'status' => $book->status,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'add_copies' => 2,
            ])
            ->assertRedirect();

        $this->assertSame(3, $book->copies()->count());
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(Book::STATUS_AVAILABLE, $book->fresh()->status);
    }
}
