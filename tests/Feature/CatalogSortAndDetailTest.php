<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogSortAndDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_can_sort_by_title(): void
    {
        $this->makeBook(['title' => 'Zulu Buku']);
        $this->makeBook(['title' => 'Alfa Buku']);

        $response = $this->get(route('books.index', ['sort' => 'title']))->assertOk();

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'Zulu Buku'),
            strpos($html, 'Alfa Buku'),
            'Buku A-Z seharusnya tampil sebelum buku Z'
        );
    }

    public function test_unknown_sort_key_falls_back_to_default_instead_of_erroring(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        // Nilai sort dari user tidak boleh bisa menyuntikkan kolom/kolom acak.
        $this->get(route('books.index', ['sort' => 'password']))->assertOk();
        $this->get(route('books.index', ['sort' => "title'; drop table books;--"]))->assertOk();
    }

    public function test_catalog_shows_empty_state_when_no_match(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        $this->get(route('books.index', ['q' => 'tidak-ada-hasil']))
            ->assertOk()
            ->assertSee('Buku tidak ditemukan');
    }

    public function test_public_can_view_book_detail(): void
    {
        $book = $this->makeBook(['title' => 'Buku Detail', 'description' => 'Sinopsis singkat.']);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Buku Detail')
            ->assertSee('Sinopsis singkat.')
            ->assertSee($book->isbn);
    }

    public function test_staff_can_open_book_edit_from_detail_page(): void
    {
        $book = $this->makeBook(['title' => 'Buku dengan Aksi Edit']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Edit buku')
            ->assertSee(route('books.edit', $book), false);
    }

    public function test_member_does_not_see_staff_book_edit_action(): void
    {
        $book = $this->makeBook(['title' => 'Buku milik katalog']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('Edit buku')
            ->assertDontSee(route('books.edit', $book), false);
    }
}
