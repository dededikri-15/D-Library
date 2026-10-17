<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoanSuspensionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_anggota_disanksi_tidak_bisa_meminjam_buku(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        // Jalur anggota: pesan penolakan dikirim sebagai toast `status`
        // (lihat LoanController::borrow) dengan batas akhir sanksi.
        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'sanksi'));

        // Tidak ada peminjaman yang tercatat — sanksi benar-benar menahan.
        $this->assertSame(0, Loan::query()->where('user_id', $member->id)->count());
    }

    public function test_pustakawan_juga_ditolak_mencatat_pinjaman_untuk_anggota_disanksi(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.store'), [
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('book_id');

        $this->assertSame(0, Loan::query()->where('user_id', $member->id)->count());
    }

    public function test_sanksi_berakhir_otomatis_setelah_72_jam(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        // Lewati tepat satu jam setelah batas sanksi.
        $this->travelTo($member->suspended_until->copy()->addHour());

        $this->assertFalse($member->refresh()->isSuspended());

        $this->actingAs($member)
            ->post(route('books.borrow', $book))
            ->assertSessionHas('status');

        $this->assertSame(1, Loan::query()->where('user_id', $member->id)->count());
    }
}
