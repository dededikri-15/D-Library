<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_anggota_bisa_menambahkan_buku_ke_favorit(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)
            ->post(route('favorites.store', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_menambahkan_buku_yang_sama_tidak_membuat_baris_ganda(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)->post(route('favorites.store', $book));
        $this->actingAs($member)->post(route('favorites.store', $book));

        $this->assertSame(1, Favorite::where('user_id', $member->id)->where('book_id', $book->id)->count());
    }

    public function test_anggota_bisa_menghapus_buku_dari_favorit(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_anggota_tidak_bisa_menghapus_favorit_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $other->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $other->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.store', $book))->assertRedirect(route('login'));
    }

    public function test_staff_tidak_bisa_menambahkan_favorit(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create();

        $this->actingAs($staff)
            ->post(route('favorites.store', $book))
            ->assertForbidden();
    }

    public function test_daftar_favorit_menampilkan_buku_yang_disimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_daftar_favorit_kosong_untuk_anggota_baru(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Belum ada buku favorit');
    }

    public function test_anggota_hanya_melihat_favoritnya_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $other->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertDontSee($book->title);
    }

    public function test_detail_buku_menampilkan_tombol_tambah_favorit(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Tambah ke favorit');
    }

    public function test_detail_buku_menampilkan_tombol_hapus_favorit(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Hapus dari favorit');
    }

    public function test_id_buku_non_numerik_menghasilkan_404(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->post(route('favorites.store', 'abc'))
            ->assertNotFound();
    }
}
