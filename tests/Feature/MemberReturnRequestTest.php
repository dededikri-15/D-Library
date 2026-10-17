<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 10.11 - Riwayat peminjaman anggota
    |--------------------------------------------------------------------------
    */

    public function test_anggota_bisa_membuka_riwayat_peminjamannya(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('Riwayat Peminjaman');
    }

    public function test_anggota_bisa_mengajukan_pengembalian_pinjamannya_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->from(route('loans.mine'))
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect(route('loans.mine'))
            ->assertSessionHas('status', 'Permintaan pengembalian dikirim ke pustakawan.');

        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
        $this->assertNotNull($loan->fresh()->return_requested_at);
        $this->assertNull($loan->fresh()->returned_at);
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }

    public function test_anggota_tidak_bisa_mengembalikan_pinjaman_milik_anggota_lain(): void
    {
        $owner = User::factory()->anggota()->create();
        $otherMember = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($otherMember)
            ->post(route('loans.mine.request-return', $loan))
            ->assertForbidden();

        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }
}
