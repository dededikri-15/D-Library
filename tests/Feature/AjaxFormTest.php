<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\SendsAjaxRequests;
use Tests\TestCase;

class AjaxFormTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;
    use SendsAjaxRequests;

    public function test_tambah_favorit_lewat_ajax_mengembalikan_json_bukan_redirect(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->asAjax()
            ->actingAs($member)
            ->post(route('favorites.store', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku ditambahkan ke favorit.')
            ->assertJsonPath('is_favorite', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_hapus_favorit_lewat_ajax_mengembalikan_json_bukan_redirect(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->asAjax()
            ->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku dihapus dari favorit.')
            ->assertJsonPath('is_favorite', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    /*
     * Dua test di bawah adalah alasan inti Task 14.7. Tanpa keduanya, AJAX
     * bisa "berhasil" sementara form aslinya sudah mati — dan tidak ada siapa
     * pun yang sadar sampai ada user yang JS-nya mati atau formnya error.
     */

    public function test_tambah_favorit_tanpa_ajax_tetap_redirect_dengan_flash(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('favorites.store', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', 'Buku ditambahkan ke favorit.');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_hapus_favorit_tanpa_ajax_tetap_redirect_dengan_flash(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->delete(route('favorites.destroy', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', 'Buku dihapus dari favorit.');
    }
}
