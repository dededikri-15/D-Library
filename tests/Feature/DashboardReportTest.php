<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Uji tampilan grafik laporan di dasbor pustakawan (Task 26.4).
 *
 * Yang diuji bukan "komponennya ada", tapi tiga hal yang gampang terlewat:
 *   1. angka di layar sama dengan data di database (bukan angka hiasan),
 *   2. grafik hanya untuk staf — anggota dan tamu tidak pernah membayarnya,
 *   3. ketika datanya kosong, halaman tetap punya jawaban yang jelas.
 *
 * Tanggal pinjam sengaja diatur lewat `startOfMonth()` supaya tidak pernah
 * jatuh di kasus akhir bulan (31 Mei dikurangi satu bulan bisa jadi 1 Mei).
 */
class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_pustakawan_melihat_grafik_dan_peringkat(): void
    {
        $book = Book::factory()->create(['title' => 'Buku Paling Laris']);
        Loan::factory()->count(2)->create(['book_id' => $book->id]);

        $member = User::factory()->anggota()->create(['name' => 'Anggota Paling Aktif']);
        Loan::factory()->count(3)->create(['user_id' => $member->id]);

        $html = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.loans_chart_title'))
            ->assertSee(__('dashboard.top_books'))
            ->assertSee(__('dashboard.top_members'))
            ->getContent();

        // Grafik selalu punya 12 baris data — termasuk bulan yang nilainya 0 —
        // karena itu yang membuat sumbu X tidak melompat.
        $this->assertSame(12, substr_count($html, '<th scope="row">'));

        // Tiap baris peringkat tertaut ke daftar terfilter, bukan ke halaman
        // detail: angka "2 pinjaman" baru bisa dipercaya kalau daftar di
        // baliknya bisa dibuka pembacanya.
        $this->assertStringContainsString(
            'href="'.route('books.index', ['q' => 'Buku Paling Laris']).'"',
            $html,
        );
        $this->assertStringContainsString(
            'href="'.route('users.index', ['q' => 'Anggota Paling Aktif']).'"',
            $html,
        );
    }

    public function test_total_grafik_sesuai_dengan_jumlah_pinjaman_di_database(): void
    {
        $this->loanAt(now()->startOfMonth());
        $this->loanAt(now()->startOfMonth());
        $this->loanAt(now()->startOfMonth()->subMonths(3));
        // Di luar rentang 12 bulan: tidak boleh ikut mengubah total.
        $this->loanAt(now()->startOfMonth()->subMonths(12));

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(
                __('dashboard.loans_chart_alt', [
                    'months' => 12,
                    'count' => number_format(3, 0, ',', '.'),
                ]),
                false,
            )
            ->assertSee(
                __('dashboard.loans_chart_total', ['count' => number_format(3, 0, ',', '.')]),
                false,
            );
    }

    /**
     * Tiga query laporan hanya boleh dibayar oleh staf. Kalau grafik ikut
     * ter-render untuk anggota/tamu, bukan cuma rugi query — teks "Buku
     * Paling Sering Dipinjam" juga menyesatkan karena mereka tidak punya
     * akses ke daftar peminjaman orang lain.
     */
    public function test_anggota_dan_tamu_tidak_melihat_grafik_laporan(): void
    {
        $chartTitle = __('dashboard.loans_chart_title');
        $topBooks = __('dashboard.top_books');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee($chartTitle)
            ->assertDontSee($topBooks);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertDontSee($chartTitle)
            ->assertDontSee($topBooks);
    }

    /**
     * Regresi untuk jebakan plural: `trans_choice` harus dikirim kunci
     * terjemahan, bukan hasil `__()`. Kalau hasil `__()` yang dikirim,
     * `Translator::localeForChoice()` tidak mengenali kiriman itu sebagai
     * kunci dan memakai `fallback_locale` ('id') — aturan jamak Indonesia
     * selalu memilih bentuk pertama, sehingga layar Inggris tampil
     * "2 loan" (tunggal) untuk jumlah 2.
     */
    public function test_satuan_peringkat_memakai_bentuk_jamak_di_bahasa_inggris(): void
    {
        $book = Book::factory()->create(['title' => 'Buku Uji Bentuk Jamak']);
        Loan::factory()->count(2)->create(['book_id' => $book->id]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2 loans');
    }

    public function test_grafik_kosong_menampilkan_empty_state_bukan_halaman_rusak(): void
    {
        $html = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            // Deskripsinya yang dipakai sebagai penanda: judul "Belum ada
            // peminjaman" juga muncul di kartu "Peminjaman Terbaru", jadi
            // menembak judul itu bisa memberi nilai palsu.
            ->assertSee(__('dashboard.loans_chart_empty_description'))
            ->assertSee(__('dashboard.rank_empty_description'))
            ->getContent();

        // Tanpa data, grafik tidak dirender sama sekali — tidak ada batang
        // setinggi 0% yang menyulitkan pembaca, dan tabelnya juga tidak ada.
        $this->assertSame(0, substr_count($html, '<th scope="row">'));
    }
}
