<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingPageGuardTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    public function test_query_page_aneh_tidak_membuat_halaman_rusak(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        foreach (['abc', '-5', '0', '3.7'] as $value) {
            $this->actingAs($member)
                ->get(route('books.read', ['book' => $book, 'page' => $value]))
                ->assertOk()
                ->assertSee('#page=', false);
        }
    }

    public function test_anggota_tidak_bisa_menyimpan_posisi_buku_milik_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = $this->borrowedBook($other, 320);

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 10,
            ]);

        // Tidak boleh membuat baris di atas nama orang lain.
        $this->assertDatabaseMissing('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_menyimpan_posisi_untuk_buku_tidak_ada_ditolak(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => 999999,
                'last_page' => 5,
            ])
            ->assertSessionHasErrors('book_id');
    }
}
