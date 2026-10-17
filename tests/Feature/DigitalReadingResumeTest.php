<?php

namespace Tests\Feature;

use App\Actions\RecordReading;
use App\Models\Book;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingResumeTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    public function test_dasbor_anggota_menampilkan_lanjut_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 64);

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Lanjut baca')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 64]), false);
    }

    public function test_membuka_pembaca_melanjutkan_dari_halaman_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 123);

        // Tanpa `?page=`, halaman baca harus memakai posisi tersimpan.
        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('#page=123', false);
    }

    public function test_halaman_yang_ada_di_url_mengalahkan_posisi_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 123);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 10]))
            ->assertOk()
            ->assertSee('#page=10', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Aturan internal RecordReading
    |--------------------------------------------------------------------------
    */

    public function test_clamp_menolak_halaman_kurang_dari_satu(): void
    {
        $action = app(RecordReading::class);
        $book = Book::factory()->create(['pages' => 100]);

        $this->assertSame(1, $action->clampPage(0, $book));
        $this->assertSame(1, $action->clampPage(-10, $book));
    }

    public function test_clamp_tidak_membatasi_buku_tanpa_jumlah_halaman(): void
    {
        $action = app(RecordReading::class);
        $book = Book::factory()->create(['pages' => 0]);

        $this->assertSame(999, $action->clampPage(999, $book));
    }

    public function test_visit_tidak_membuat_baris_ganda(): void
    {
        $action = app(RecordReading::class);
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $action->visit($member, $book);
        $action->visit($member, $book);
        $action->savePage($member, $book, 9);

        $this->assertSame(1, ReadingHistory::where('user_id', $member->id)->where('book_id', $book->id)->count());
    }
}
