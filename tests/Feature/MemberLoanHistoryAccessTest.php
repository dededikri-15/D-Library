<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLoanHistoryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_peminjaman_menampilkan_angka_ringkasan(): void
    {
        $member = User::factory()->anggota()->create();

        $active = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $late = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $done = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        Loan::factory()->create(['user_id' => $member->id, 'book_id' => $active->id]);
        Loan::factory()->overdue()->create(['user_id' => $member->id, 'book_id' => $late->id]);
        Loan::factory()->returned()->create(['user_id' => $member->id, 'book_id' => $done->id]);

        $this->actingAs($member)->get(route('loans.mine'))->assertOk();

        $view = $this->actingAs($member)->get(route('loans.mine'));

        // 2 aktif (borrowed + overdue), 1 terlambat, 1 sudah kembali.
        $this->assertSame(2, $view->viewData('activeCount'));
        $this->assertSame(1, $view->viewData('overdueCount'));
        $this->assertSame(1, $view->viewData('returnedCount'));
    }

    public function test_anggota_tidak_bisa_membuka_riwayat_peminjaman_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $secretBook = Book::factory()->create(['title' => 'Buku Rahasia']);

        Loan::factory()->create(['user_id' => $other->id, 'book_id' => $secretBook->id]);

        // Tidak ada parameter user_id di URL sama sekali, jadi tidak ada
        // cara untuk meminta milik orang lain.
        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertDontSee('Buku Rahasia');
    }

    public function test_riwayat_peminjaman_hanya_untuk_anggota(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.mine'))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login_dari_riwayat_peminjaman(): void
    {
        $this->get(route('loans.mine'))->assertRedirect(route('login'));
    }

    public function test_halaman_riwayat_menampilkan_empty_state_jika_belum_pernah_meminjam(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('Belum ada riwayat peminjaman');
    }
}
