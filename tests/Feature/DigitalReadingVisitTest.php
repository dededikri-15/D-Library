<?php

namespace Tests\Feature;

use App\Actions\RecordReading;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingVisitTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 11.5 - Mencatat aktivitas membaca
    |--------------------------------------------------------------------------
    */

    public function test_staff_bisa_membaca_untuk_pratinjau_tanpa_membuat_riwayat(): void
    {
        $book = $this->borrowedBook();
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee(route('books.file', $book).'#page=1', false);

        // Pratinjau staff tidak masuk daftar riwayat baca: tidak ada halaman
        // yang menampilkan data itu, jadi hanya jadi sampah baris.
        $this->assertDatabaseMissing('reading_histories', [
            'user_id' => $staff->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_membuka_pembaca_mencatat_kunjungan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_membuka_pembaca_untuk_pertama_kali_tidak_gagal(): void
    {
        // Regresi: `last_page` punya default 0 di database, tapi objek
        // Eloquent yang baru dibuat tidak punya atribut itu sehingga
        // `clampPage(null)` menyebabkan TypeError -> HTTP 500.
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('#page=1', false);

        $this->assertSame(1, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }

    public function test_membuka_pembaca_lagi_tidak_menimpa_posisi_yang_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        app(RecordReading::class)->savePage($member, $book, 120);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        // Posisi harus tetap 120, bukan kembali ke 1.
        $this->assertSame(120, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }
}
