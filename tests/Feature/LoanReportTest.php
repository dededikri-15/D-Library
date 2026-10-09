<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Support\LoanReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Uji sumber data grafik dasbor pustakawan (Task 26.1).
 *
 * Yang diuji bukan "query-nya jalan", tapi keputusan yang gampang salah:
 * bulan kosong harus tetap muncul, pengelompokan harus memakai tanggal
 * pinjam (bukan waktu baris dibuat), dan buku/anggota tanpa pinjaman tidak
 * boleh tampil di peringkat.
 *
 * Semua test memakai tanggal eksplisit lewat `startOfMonth()` supaya tidak
 * pernah jatuh di kasus akhir bulan (31 Mei dikurangi satu bulan bisa jadi
 * 1 Mei, bukan 1 April — Carbon mengizinkan meluber).
 */
class LoanReportTest extends TestCase
{
    use RefreshDatabase;

    private LoanReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report = new LoanReport;
    }

    /**
     * Buat pinjaman dengan tanggal pinjam yang ditentukan dan tempo yang
     * masuk akal relatif terhadap tanggal itu.
     */
    private function loanAt(Carbon $borrowedAt, array $overrides = []): Loan
    {
        return Loan::factory()->create([
            'borrowed_at' => $borrowedAt,
            'due_at' => $borrowedAt->copy()->addDays(config('perpustakaan.loan.duration_days')),
            ...$overrides,
        ]);
    }

    public function test_per_month_mengembalikan_bulan_kosong_secara_berurutan(): void
    {
        $items = $this->report->perMonth();

        $this->assertCount(12, $items);
        $this->assertSame(now()->startOfMonth()->format('Y-m'), $items->last()['key']);
        $this->assertSame(now()->startOfMonth()->subMonths(11)->format('Y-m'), $items->first()['key']);
        $this->assertSame(0, $items->first()['value']);

        $keys = $items->pluck('key')->all();
        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys, 'Bulan harus urut kronologis dari yang paling lama.');
    }

    public function test_per_month_menghitung_peminjaman_pada_bulan_yang_sama(): void
    {
        $this->loanAt(now()->startOfMonth());
        $this->loanAt(now()->startOfMonth());
        $this->loanAt(now()->startOfMonth()->subMonth());
        $this->loanAt(now()->startOfMonth()->subMonths(11));
        // Di luar rentang 12 bulan: tidak boleh ikut terhitung.
        $this->loanAt(now()->startOfMonth()->subMonths(12));

        $byKey = $this->report->perMonth()->keyBy('key');

        $this->assertSame(2, $byKey->get(now()->startOfMonth()->format('Y-m'))['value']);
        $this->assertSame(1, $byKey->get(now()->startOfMonth()->subMonth()->format('Y-m'))['value']);
        $this->assertSame(1, $byKey->get(now()->startOfMonth()->subMonths(11)->format('Y-m'))['value']);
        $this->assertNull($byKey->get(now()->startOfMonth()->subMonths(12)->format('Y-m')));
    }

    /**
     * Peminjaman yang dikembalikan tetap dihitung di bulan ia dipinjam,
     * bukan di bulan dikembalikan.
     */
    public function test_per_month_memakai_tanggal_pinjam_bukan_tanggal_pengembalian(): void
    {
        $borrowed = now()->startOfMonth()->subMonths(2);
        $this->loanAt($borrowed, [
            'returned_at' => now()->startOfMonth(),
            'status' => Loan::STATUS_RETURNED,
        ]);

        $byKey = $this->report->perMonth()->keyBy('key');

        $this->assertSame(1, $byKey->get($borrowed->format('Y-m'))['value']);
        $this->assertSame(0, $byKey->get(now()->startOfMonth()->format('Y-m'))['value']);
    }

    public function test_top_books_urut_dan_membuang_yang_tanpa_pinjaman(): void
    {
        $mostBorrowed = Book::factory()->create();
        $lessBorrowed = Book::factory()->create();
        Book::factory()->create();

        Loan::factory()->count(3)->create(['book_id' => $mostBorrowed->id]);
        Loan::factory()->create(['book_id' => $lessBorrowed->id]);

        $books = $this->report->topBooks();

        $this->assertSame(
            [$mostBorrowed->id, $lessBorrowed->id],
            $books->pluck('id')->all(),
        );
        $this->assertSame(3, $books->first()->loans_count);
    }

    public function test_top_books_menghormati_batas_jumlah(): void
    {
        $books = Book::factory()->count(3)->create();
        foreach ($books as $book) {
            Loan::factory()->create(['book_id' => $book->id]);
        }

        $this->assertCount(2, $this->report->topBooks(2));
    }

    public function test_top_borrowers_hanya_menghitung_anggota(): void
    {
        $activeMember = User::factory()->anggota()->create();
        $quietMember = User::factory()->anggota()->create();
        $librarian = User::factory()->pustakawan()->create();

        Loan::factory()->count(4)->create(['user_id' => $activeMember->id]);
        Loan::factory()->create(['user_id' => $quietMember->id]);
        // Pustakawan tidak bisa meminjam lewat aplikasi (route `role:anggota`),
        // jadi namanya tidak boleh muncul di peringkat.
        Loan::factory()->create(['user_id' => $librarian->id]);

        $members = $this->report->topBorrowers();

        $this->assertSame(
            [$activeMember->id, $quietMember->id],
            $members->pluck('id')->all(),
        );
        $this->assertSame(4, $members->first()->loans_count);
    }

    public function test_laporan_kosong_ketika_belum_ada_peminjaman(): void
    {
        $items = $this->report->perMonth();

        $this->assertCount(12, $items);
        $this->assertSame(0, $items->sum('value'));
        $this->assertCount(0, $this->report->topBooks());
        $this->assertCount(0, $this->report->topBorrowers());
    }
}
