<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesCatalogBook;
use Tests\TestCase;

class CatalogQueryTest extends TestCase
{
    use ProvidesCatalogBook;
    use RefreshDatabase;

    public function test_filters_combine_and_pagination_links_keep_the_query_string(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $author = Author::factory()->create(['name' => 'Andi Prasetyo']);

        Book::factory()->count(15)->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
        ]);
        $this->makeBook(['title' => 'Buku Lain']);

        $response = $this->get(route('books.index', ['category' => 'fiksi', 'author' => 'Andi Prasetyo']))
            ->assertOk();

        // Dua filter harus menyempitkan hasil secara bersamaan (AND), bukan
        // salah satu saja.
        $this->assertSame(15, $response->viewData('books')->total());
        $this->assertStringNotContainsString('Buku Lain', $response->getContent());

        // Halaman berikutnya harus membawa filter yang sama. Kalau filter hilang
        // dari URL pagination, klik "2" akan mengembalikan seluruh katalog
        // tanpa filter sama sekali.
        $nextUrl = $response->viewData('books')->nextPageUrl();
        parse_str((string) parse_url($nextUrl, PHP_URL_QUERY), $nextQuery);
        $this->assertSame('fiksi', $nextQuery['category'] ?? null);
        $this->assertSame('Andi Prasetyo', $nextQuery['author'] ?? null);
        $this->assertSame('2', $nextQuery['page'] ?? null);

        $next = $this->get($nextUrl)->assertOk();
        $this->assertSame(15, $next->viewData('books')->total());
    }

    public function test_catalog_can_search_by_title(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);
        $this->makeBook(['title' => 'Buku Beta']);

        $this->get(route('books.index', ['q' => 'Alfa']))
            ->assertOk()
            ->assertSee('Buku Alfa')
            ->assertDontSee('Buku Beta');
    }

    public function test_catalog_can_filter_by_category(): void
    {
        $fiksi = Category::factory()->create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $sains = Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->makeBook(['title' => 'Novel Alpha', 'category_id' => $fiksi->id]);
        $this->makeBook(['title' => 'Buku Beta', 'category_id' => $sains->id]);

        $this->get(route('books.index', ['category' => 'fiksi']))
            ->assertOk()
            ->assertSee('Novel Alpha')
            ->assertDontSee('Buku Beta');
    }

    public function test_catalog_can_filter_by_author(): void
    {
        $andi = Author::factory()->create(['name' => 'Andi Prasetyo']);
        $budi = Author::factory()->create(['name' => 'Budi Santoso']);

        $this->makeBook(['title' => 'Buku Andi', 'author_id' => $andi->id]);
        $this->makeBook(['title' => 'Buku Budi', 'author_id' => $budi->id]);

        $this->get(route('books.index', ['author' => 'Andi Prasetyo']))
            ->assertOk()
            ->assertSee('Buku Andi')
            ->assertDontSee('Buku Budi');
    }
}
