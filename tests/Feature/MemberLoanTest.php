<?php

namespace Tests\Feature;

use App\Actions\BorrowBook;
use App\Actions\MarkOverdueLoans;
use App\Exceptions\LoanNotPossibleException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Task 10.1, 10.10, 10.11, dan 10.14.
 *
 * LoanFlowTest (sudah ada sebelumnya) menguji jalur pustakawan. File ini
 * menguji jalur anggota: peminjaman mandiri dari detail buku, penyegaran
 * status overdue, dan halaman riwayat peminjaman.
 */
class MemberLoanTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 10.1 - Peminjaman mandiri oleh anggota
    |--------------------------------------------------------------------------
    */

    public function test_anggota_bisa_meminjam_buku_yang_tersedia(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status');

        $loan = Loan::where('book_id', $book->id)->firstOrFail();

        $this->assertSame($member->id, $loan->user_id);
        $this->assertSame(Loan::STATUS_BORROWED, $loan->status);
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
        $this->assertTrue($loan->borrowed_at->isSameDay(now()));
    }

    public function test_anggota_tidak_bisa_meminjam_buku_yang_sedang_dipinjam_orang_lain(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create(['book_id' => $book->id, 'status' => Loan::STATUS_BORROWED]);

        $this->actingAs(User::factory()->anggota()->create())
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status');

        // Pesan harus menyebut orang lain, bukan "tidak bisa dipinjam" generik.
        $this->assertStringContainsString('anggota lain', session('status'));

        $this->assertSame(1, Loan::where('book_id', $book->id)->count());
    }

    public function test_anggota_tidak_bisa_meminjam_buku_yang_sudah_dipinjam_sendiri(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book));

        // Pesan harus berbeda dari kasus "dipinjam anggota lain", supaya
        // orang tidak mengira bukunya hilang.
        $this->assertStringContainsString('Anda sedang meminjam', session('status'));

        $this->assertSame(1, Loan::where('book_id', $book->id)->count());
    }

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

    public function test_tombol_pinjam_menghilang_saat_anggota_meminjam_buku_yang_sama(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('sedang meminjam buku ini')
            ->assertDontSee('action="'.route('books.borrow', $book).'"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | 10.13 - Peminjaman ganda, diuji langsung di action
    |--------------------------------------------------------------------------
    |
    | Yang diuji di sini bukan HTTP response, tapi aturan bisnisnya, karena
    | dua jalur peminjaman (anggota & staff) sama-sama melewati BorrowBook.
    |
    */

    public function test_borrow_book_menolak_buku_yang_sudah_dipinjam(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $first = User::factory()->anggota()->create();
        $second = User::factory()->anggota()->create();

        app(BorrowBook::class)->handle($first, $book->id);

        $this->expectException(LoanNotPossibleException::class);
        app(BorrowBook::class)->handle($second, $book->id);
    }

    public function test_borrow_book_melempar_untuk_buku_yang_tidak_ada(): void
    {
        $this->expectException(LoanNotPossibleException::class);

        app(BorrowBook::class)->handle(User::factory()->anggota()->create(), 999999);
    }

    public function test_jatuh_tempo_dihitung_dari_config(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $borrowedAt = Carbon::parse('2026-01-10 09:00:00');

        $loan = app(BorrowBook::class)->handle(
            User::factory()->anggota()->create(),
            $book->id,
            $borrowedAt
        );

        $this->assertTrue($loan->due_at->isSameDay($borrowedAt->copy()->addDays(14)));
    }

    public function test_tempo_peminjaman_bisa_diubah_lewat_config(): void
    {
        config(['perpustakaan.loan.duration_days' => 3]);

        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);
        $borrowedAt = Carbon::parse('2026-01-10 09:00:00');

        $loan = app(BorrowBook::class)->handle(
            User::factory()->anggota()->create(),
            $book->id,
            $borrowedAt
        );

        $this->assertTrue($loan->due_at->isSameDay($borrowedAt->copy()->addDays(3)));
    }

    /*
    |--------------------------------------------------------------------------
    | 10.10 - Status overdue
    |--------------------------------------------------------------------------
    */

    public function test_peminjaman_lewat_tempo_berubah_jadi_overdue(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDay(),
        ]);

        $changed = app(MarkOverdueLoans::class)->handle();

        $this->assertSame(1, $changed);
        $this->assertSame(Loan::STATUS_OVERDUE, $loan->fresh()->status);
    }

    public function test_peminjaman_yang_belum_lewat_tempo_tidak_diubah(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->addDays(3),
        ]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
    }

    public function test_jatuh_tempo_tepat_hari_ini_belum_terlambat(): void
    {
        $loan = Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->addHours(6),
        ]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_BORROWED, $loan->fresh()->status);
    }

    public function test_peminjaman_yang_sudah_dikembalikan_tidak_jadi_overdue(): void
    {
        $loan = Loan::factory()->returned()->create(['due_at' => now()->subDays(10)]);

        $this->assertSame(0, app(MarkOverdueLoans::class)->handle());
        $this->assertSame(Loan::STATUS_RETURNED, $loan->fresh()->status);
    }

    public function test_buku_terlambat_tetap_berstatus_borrowed(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDays(3),
        ]);

        app(MarkOverdueLoans::class)->handle();

        // Buku fisiknya masih di luar pustaka. Kalau jadi available, anggota
        // lain bisa langsung meminjam buku yang belum dikembalikan.
        $this->assertSame(Book::STATUS_BORROWED, $book->fresh()->status);
    }

    public function test_artisian_command_menandai_peminjaman_terlambat(): void
    {
        Loan::factory()->create([
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDay(),
        ]);

        $this->artisan('loans:mark-overdue')
            ->expectsOutputToContain('1 peminjaman')
            ->assertSuccessful();
    }

    public function test_scheduler_terjadwal_setiap_jam(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'loans:mark-overdue'));

        $this->assertCount(1, $events, 'Command overdue harus dijadwalkan.');
        $this->assertSame('0 * * * *', $events->first()->expression);
    }

    public function test_halaman_peminjaman_menampilkan_status_overdue_terbarui(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
            'due_at' => now()->subDays(2),
        ]);

        // Halaman harus menyegarkan statusnya sendiri, tanpa bergantung pada
        // scheduler yang mungkin tidak jalan di mesin(test.
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('loans.index'))
            ->assertOk();

        $this->assertSame(Loan::STATUS_OVERDUE, $loan->fresh()->status);
    }

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

    public function test_tanggal_jatuh_tempo_diisi_otomatis_bila_tidak_disertakan(): void
    {
        // `borrowed_at` dan `due_at` NOT NULL di database. Tanpa hook
        // `creating` di model, setiap pemanggil yang lupa mengisinya gagal
        // dengan QueryException yang sulit dibaca.
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $loan = Loan::create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->assertNotNull($loan->borrowed_at);
        $this->assertSame(
            Loan::dueAt($loan->borrowed_at)->toDateString(),
            $loan->due_at->toDateString(),
        );
    }

    public function test_tanggal_jatuh_tempo_eksplisit_tidak_ditimpa(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();
        $custom = now()->addDays(99);

        $loan = Loan::create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now(),
            'due_at' => $custom,
        ]);

        $this->assertSame($custom->toDateString(), $loan->due_at->toDateString());
    }
}
