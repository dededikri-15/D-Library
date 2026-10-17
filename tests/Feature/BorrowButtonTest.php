<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_anggota_tidak_bisa_meminjam_buku_nonaktif(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->anggota()->create())
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book));

        $this->assertStringContainsString('tidak aktif', session('status'));
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_tamu_diarahkan_ke_login_sebelum_meminjam(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->post(route('books.borrow', $book))->assertRedirect(route('login'));
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_pustakawan_tidak_bisa_meminjam_untuk_diri_sendiri(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        // Staff punya halaman /peminjaman untuk mencatat peminjaman anggota.
        // Route anggota sengaja tidak memberi mereka jalan lain.
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.borrow', $book))
            ->assertForbidden();

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_tombol_pinjam_tampil_untuk_anggota_saat_buku_tersedia(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Pinjam Buku')
            // Formulir harus benar-benar menuju route borrow, bukan teks mati.
            ->assertSee(route('books.borrow', $book), false);
    }

    public function test_tombol_pinjam_dimatikan_saat_buku_sedang_dipinjam(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('dipinjam anggota lain')
            // Tidak boleh ada form yang mengarah ke route borrow.
            ->assertDontSee('action="'.route('books.borrow', $book).'"', false);
    }
}
