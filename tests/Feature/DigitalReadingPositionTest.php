<?php

namespace Tests\Feature;

use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingPositionTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    public function test_membuka_pembaca_memperbarui_waktu_baca_terakhir(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 50,
            'last_read_at' => now()->subWeek(),
        ]);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        $history = ReadingHistory::where('book_id', $book->id)->firstOrFail();
        $this->assertTrue($history->last_read_at->isAfter(now()->subDay()));
    }

    /*
    |--------------------------------------------------------------------------
    | 11.6 - Menyimpan halaman terakhir
    |--------------------------------------------------------------------------
    */

    public function test_anggota_bisa_menyimpan_posisi_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->from(route('books.read', $book))
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 88,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 88,
        ]);
    }

    public function test_halaman_tidak_boleh_melebihi_jumlah_halaman_buku(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 99999,
            ]);

        $this->assertSame(320, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }

    public function test_query_page_dijepit_oleh_server(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 999999]))
            ->assertOk()
            ->assertSee('#page=320', false);
    }
}
