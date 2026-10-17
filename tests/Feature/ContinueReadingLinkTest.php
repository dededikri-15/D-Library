<?php

namespace Tests\Feature;

use App\Actions\RecordReading;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class ContinueReadingLinkTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 11.7 - Fitur lanjut membaca
    |--------------------------------------------------------------------------
    */

    public function test_riwayat_baca_menampilkan_tautan_lanjut_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 77);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('Lanjut baca')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 77]), false);
    }

    public function test_riwayat_baca_tidak_menampilkan_lanjut_baca_untuk_buku_tanpa_file(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['file' => null, 'pages' => 100]);

        app(RecordReading::class)->savePage($member, $book, 20);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertDontSee('Lanjut baca');
    }

    public function test_detail_buku_menampilkan_tombol_lanjut_dari_halaman_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 55);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Lanjut dari halaman 55')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 55]), false);
    }

    public function test_detail_buku_menampilkan_baca_buku_biasa_bila_belum_pernah_membaca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Baca Buku')
            ->assertDontSee('Lanjut dari halaman');
    }
}
