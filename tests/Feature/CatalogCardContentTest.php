<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogCardContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_card_shows_required_fields(): void
    {
        $category = Category::factory()->create(['name' => 'Teknologi']);
        $author = Author::factory()->create(['name' => 'Andi Prasetyo']);
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Digital']);

        // PRD §4: card memuat cover, judul, penulis, kategori, tahun terbit.
        $this->makeBook([
            'title' => 'Buku Lengkap',
            'publication_year' => 2024,
            'category_id' => $category->id,
            'author_id' => $author->id,
            'publisher_id' => $publisher->id,
        ]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Lengkap')
            ->assertSee('Teknologi')
            ->assertSee('Andi Prasetyo')
            ->assertSee('2024');
    }

    public function test_book_detail_shows_publisher_isbn_and_pages(): void
    {
        $book = $this->makeBook(['pages' => 320]);

        // PRD §5: detail memuat penerbit, ISBN, jumlah halaman, dan status.
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee($book->publisher->name)
            ->assertSee($book->isbn)
            ->assertSee('320');
    }
}
