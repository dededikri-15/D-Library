<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesCatalogBook;
use Tests\TestCase;

class BookCatalogTest extends TestCase
{
    use ProvidesCatalogBook;
    use RefreshDatabase;

    /**
     * Menu "Katalog" staf membuka `/buku?lihat=katalog`: tampilan kartu
     * seperti katalog publik, bukan halaman kelola. Buku nonaktif tetap
     * disembunyikan karena mode ini bukan mode pengelolaan.
     *
     * Yang dibedakan bukan judul halama (keduanya memakai judul "Katalog
     * Buku" di tag <title>), tapi deskripsi mode dan aksi staf.
     */
    public function test_staff_can_browse_the_public_catalog_view(): void
    {
        $this->makeBook(['title' => 'Buku Katalog Staf']);
        $this->makeBook(['title' => 'Buku Nonaktif Staf', 'status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.index', ['lihat' => 'katalog']))
            ->assertOk()
            ->assertSee(__('catalog.description'))
            ->assertSee(__('catalog.collection_eyebrow'))
            ->assertSee('Buku Katalog Staf')
            ->assertDontSee(__('catalog.management_description'))
            ->assertDontSee(__('catalog.management_eyebrow'))
            ->assertDontSee('Buku Nonaktif Staf');
    }

    public function test_public_can_view_catalog(): void
    {
        $this->makeBook(['title' => 'Buku Alfa']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku Alfa');
    }

    public function test_staff_can_manage_existing_books_and_edit_the_cover(): void
    {
        $book = $this->makeBook(['title' => 'Buku untuk Diedit']);
        $inactiveBook = $this->makeBook([
            'title' => 'Buku Nonaktif untuk Diedit',
            'status' => Book::STATUS_INACTIVE,
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Kelola Buku')
            ->assertSee('Tambah buku')
            ->assertSee($book->title)
            ->assertSee($inactiveBook->title)
            ->assertSee(route('books.edit', $book), false);
    }

    public function test_member_catalog_does_not_show_book_management_actions(): void
    {
        $book = $this->makeBook(['title' => 'Buku Katalog Anggota']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Katalog Buku')
            ->assertDontSee('Kelola Buku')
            ->assertDontSee(route('books.edit', $book), false);
    }
}
