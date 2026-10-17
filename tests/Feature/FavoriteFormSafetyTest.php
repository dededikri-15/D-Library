<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class FavoriteFormSafetyTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_form_favorit_menanda_fallback_yang_aman(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        // `data-ajax-fallback` hanya boleh dipakai form yang aksinya idempoten.
        // Kalau atribut ini hilang, `initAjaxForms` tidak akan pernah mengirim
        // ulang request — dan itu memang pilihan yang benar untuk form yang
        // belum diaudit, tapi harus terlihat di sini supaya tidak dihapus
        // tanpa dipikirkan.
        $this->assertStringContainsString('data-ajax-fallback', $content);
    }

    public function test_form_favorit_tidak_ada_untuk_staff_dan_tamu(): void
    {
        $book = Book::factory()->create();

        // Pustakawan mengelola buku, bukan favorit miliknya sendiri.
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('data-favorite-toggle', false);

        // Tamu diarahkan ke login, jadi tidak ada form yang perlu ditampilkan.
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('data-favorite-toggle', false);
    }

    public function test_aksi_luar_javascript_tidak_berubah_untuk_form_lain(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        // Form peminjaman tetap jalur biasa: memuat ulang halaman memang
        // benar di sana karena status buku berubah di banyak tempat sekaligus.
        $this->actingAs($member)
            ->post(route('books.borrow', $book))
            ->assertRedirect();

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertDontSee('data-ajax', false);
    }
}
