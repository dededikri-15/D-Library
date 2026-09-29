<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_menampilkan_tombol_pratinjau_cepat(): void
    {
        $book = Book::factory()->create(['title' => 'Negeri Van Oranje']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-modal-open="#pratinjau-buku-'.$book->id.'"', false)
            ->assertSee('Pratinjau cepat');
    }

    public function test_dialog_pratinjau_dibangun_dengan_elemen_dialog_asli(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('<dialog id="pratinjau-buku-'.$book->id.'" data-modal', false)
            ->assertSee('aria-labelledby="pratinjau-buku-'.$book->id.'-judul"', false)
            ->assertSee('data-modal-close', false);
    }

    public function test_dialog_pratinjau_dirender_dalam_keadaan_tertutup(): void
    {
        Book::factory()->create();

        $content = $this->get(route('books.index'))->assertOk()->getContent();

        // Tanpa atribut `open`, `<dialog>` tidak tampil walaupun isinya dirender.
        $this->assertDoesNotMatchRegularExpression('/<dialog[^>]*\sopen[\s>]/', $content);
    }

    public function test_dialog_pratinjau_menampilkan_ringkasan_buku(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laskar Pelangi',
            'description' => 'Kisah anak-anak diasek Bambu orang tuanya.',
        ]);
        $book->category()->associate(Category::factory()->create(['name' => 'Fiksi']))->save();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Laskar Pelangi')
            ->assertSee('Kisah anak-anak diasek Bambu orang tuanya.')
            ->assertSee('Fiksi')
            ->assertSee(route('books.show', $book), escape: false);
    }

    public function test_katalog_kosong_tidak_menampilkan_dialog(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Buku tidak ditemukan')
            ->assertDontSee('<dialog', false);
    }

    public function test_halaman_kelola_buku_pustakawan_tetap_punya_pratinjau_dan_edit(): void
    {
        $book = Book::factory()->create();
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('data-modal-open="#pratinjau-buku-'.$book->id.'"', false)
            ->assertSee(route('books.edit', $book), escape: false);
    }
}
