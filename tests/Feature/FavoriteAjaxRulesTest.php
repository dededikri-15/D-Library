<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\SendsAjaxRequests;

class FavoriteAjaxRulesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesLibraryUsers;
    use SendsAjaxRequests;

    public function test_hapus_favorit_yang_sudah_tidak_ada_melapor_jujur(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        // Dua request hapus berurutan: yang kedua tidak menghapus apa pun.
        $this->asAjax()->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku dihapus dari favorit.');

        $this->asAjax()->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku ini memang tidak ada di favorit.')
            ->assertJsonPath('is_favorite', false);
    }

    public function test_menambah_favorit_dua_kali_tetap_aman(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->asAjax()->actingAs($member)->post(route('favorites.store', $book))->assertOk();
        $this->asAjax()->actingAs($member)->post(route('favorites.store', $book))->assertOk();

        $this->assertSame(
            1,
            Favorite::query()->where('user_id', $member->id)->where('book_id', $book->id)->count(),
        );
    }

    public function test_aksi_favorit_masih_melarang_orang_lain(): void
    {
        $owner = $this->member();
        $other = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $owner->id, 'book_id' => $book->id]);

        // Controller sudah dibatasi lewat relasi `$user->favorites()`, jadi
        // favorit orang lain tidak boleh ikut terhapus meski id-nya benar.
        $this->asAjax()->actingAs($other)
            ->delete(route('favorites.destroy', $book))
            ->assertOk();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_tamu_tidak_bisa_menambah_favorit_lewat_ajax(): void
    {
        $book = Book::factory()->create();

        $this->asAjax()
            ->post(route('favorites.store', $book))
            ->assertUnauthorized();

        $this->assertDatabaseCount('favorites', 0);
    }
}
