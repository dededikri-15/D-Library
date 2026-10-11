<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCopyCodeTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(string $title, string $categorySlug, string $authorName): Book
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => $categorySlug],
            ['name' => ucfirst($categorySlug)],
        );

        return Book::factory()->create([
            'title' => $title,
            'category_id' => $category->id,
            'author_id' => Author::factory()->create(['name' => $authorName])->id,
            'publisher_id' => Publisher::factory()->create()->id,
        ]);
    }

    public function test_first_copy_uses_ddc_code_from_title_and_category(): void
    {
        $book = $this->makeBook('Sejarah Kota Nusantara', 'sejarah', 'Dewi Lestari');

        $this->assertSame('959.8/LES/01', $book->copies()->first()->inventory_code);
    }

    public function test_additional_copies_get_sequential_numbers(): void
    {
        $book = $this->makeBook('Sejarah Kota Nusantara', 'sejarah', 'Dewi Lestari');

        $book->copies()->create(['status' => 'available']);
        $book->copies()->create(['status' => 'available']);

        $codes = $book->copies()->orderBy('inventory_code')->pluck('inventory_code')->all();

        $this->assertSame(['959.8/LES/01', '959.8/LES/02', '959.8/LES/03'], $codes);
    }

    public function test_title_keyword_wins_over_category(): void
    {
        // Judul menyebut "pemrograman" -> 005.1 (DDC ilmu komputer),
        // bukan 600 milik kategori Teknologi.
        $book = $this->makeBook('Dasar-Dasar Pemrograman PHP', 'teknologi', 'Andi Prasetyo');

        $this->assertSame('005.1/PRA/01', $book->copies()->first()->inventory_code);
    }

    public function test_category_is_used_when_title_has_no_keyword(): void
    {
        $book = $this->makeBook('Lembutnya Senja', 'fiksi', 'Budi Santoso');

        $this->assertSame('899.22/SAN/01', $book->copies()->first()->inventory_code);
    }

    public function test_two_books_sharing_ddc_and_author_continue_the_same_sequence(): void
    {
        $first = $this->makeBook('Sejarah Kota Nusantara', 'sejarah', 'Dewi Lestari');
        $first->copies()->create(['status' => 'available']);

        $second = $this->makeBook('Pahlawan yang Terlupakan', 'sejarah', 'Dewi Lestari');

        $codes = Book::query()
            ->whereIn('id', [$first->id, $second->id])
            ->with('copies')
            ->get()
            ->flatMap->copies
            ->pluck('inventory_code')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['959.8/LES/01', '959.8/LES/02', '959.8/LES/03'], $codes);
    }

    public function test_changing_category_regenerates_copy_codes(): void
    {
        $book = $this->makeBook('Sejarah Kota Nusantara', 'sejarah', 'Dewi Lestari');

        $this->assertSame('959.8/LES/01', $book->copies()->first()->inventory_code);

        $book->update([
            'category_id' => Category::factory()->create(['name' => 'Agama', 'slug' => 'agama'])->id,
        ]);

        // "Sejarah Kota Nusantara" masih mengandung kata kunci judul, jadi
        // DDC judul menang dan kode tidak berubah. Uji jalur kategori dengan
        // judul yang bersih dari kata kunci.
        $plain = $this->makeBook('Rumah Tanpa Pintu', 'sastra', 'Rina Kusuma');
        $this->assertSame('899.22/KUS/01', $plain->copies()->first()->inventory_code);

        $plain->update([
            'category_id' => Category::factory()->create(['name' => 'Sains', 'slug' => 'sains'])->id,
        ]);

        $this->assertSame('500/KUS/01', $plain->copies()->first()->fresh()->inventory_code);
    }

    public function test_single_word_author_gets_padded_mark(): void
    {
        $book = $this->makeBook('Puisi untuk Langit', 'sastra', 'Rina');

        $this->assertSame('899.22/RIN/01', $book->copies()->first()->inventory_code);
    }
}
