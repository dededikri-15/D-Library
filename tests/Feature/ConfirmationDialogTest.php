<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmationDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_menyediakan_satu_dialog_konfirmasi_bersama(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $content = $this->actingAs($member)->get(route('favorites.index'))->getContent();

        $this->assertSame(1, substr_count($content, 'data-confirm-accept'));
        $this->assertStringContainsString('id="konfirmasi-tindakan"', $content);
        $this->assertStringContainsString('data-confirm-message', $content);
        $this->assertStringContainsString('role="alertdialog"', $content);
    }

    public function test_dialog_konfirmasi_tidak_dirender_untuk_tamu(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertDontSee('data-confirm-accept', false);
    }

    public function test_dialog_konfirmasi_tertutup_secara_default(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $content = $this->actingAs($member)->get(route('favorites.index'))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<dialog[^>]*id="konfirmasi-tindakan"[^>]*\sopen[\s>]/',
            $content
        );
    }

    public function test_form_hapus_memakai_atribut_konfirmasi_dengan_judul(): void
    {
        $book = Book::factory()->create();
        $librarian = User::factory()->pustakawan()->create();

        $this->actingAs($librarian)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('data-confirm="Hapus buku ini beserta data terkait?"', false)
            ->assertSee('data-confirm-title="Hapus buku"', false);
    }

    public function test_setiap_aksi_destruktif_memakai_konfirmasi(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);
        $book = Book::factory()->create();
        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('data-confirm="Hapus '.$book->title.' dari favorit?"', false)
            ->assertSee('data-confirm-title="Hapus dari favorit"', false);
    }

    public function test_master_data_memakai_konfirmasi_berjudul(): void
    {
        $librarian = User::factory()->pustakawan()->create();
        $category = Category::factory()->create(['name' => 'Sastra']);

        $this->actingAs($librarian)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('data-confirm-title="Hapus kategori"', false);
    }

    public function test_konfirmasi_hanya_menahan_form_di_layar_bukan_menahan_request(): void
    {
        // Dialog hanya menahan submit di browser. Form tetap terkirim ke server
        // seperti biasa, jadi pengaman sesungguhnya tetap milik server.
        $librarian = User::factory()->pustakawan()->create();
        $category = Category::factory()->create();

        $this->actingAs($librarian)
            ->from(route('categories.index'))
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_aksi_tetap_berjalan_tanpa_javascript(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);
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
}
