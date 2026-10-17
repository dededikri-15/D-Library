<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemberLoanHistoryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_anggota_menampilkan_tombol_ajukan_pengembalian(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('Ajukan pengembalian')
            ->assertSee(route('loans.mine.request-return', $loan), false);
    }

    public function test_riwayat_anggota_menampilkan_status_menunggu_konfirmasi(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'return_requested_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi pustakawan')
            ->assertDontSee('Ajukan pengembalian');
    }

    public function test_tanggal_pinjam_dan_jatuh_tempo_ditampilkan_dalam_waktu_jakarta(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => Carbon::parse('2026-09-28 18:10:00', 'UTC'),
            'due_at' => Carbon::parse('2026-10-12 18:10:00', 'UTC'),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('29 Sep 2026')
            ->assertSee('13 Oct 2026');
    }

    public function test_riwayat_hanya_menampilkan_pinjaman_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();

        $myBook = Book::factory()->create(['title' => 'Buku Milik Saya']);
        $theirBook = Book::factory()->create(['title' => 'Buku Milik Orang Lain']);

        Loan::factory()->create(['user_id' => $member->id, 'book_id' => $myBook->id]);
        Loan::factory()->create(['user_id' => $other->id, 'book_id' => $theirBook->id]);

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('Buku Milik Saya')
            ->assertDontSee('Buku Milik Orang Lain');
    }
}
