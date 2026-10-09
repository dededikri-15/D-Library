<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Uji tampilan grafik aktivitas di dasbor anggota (Task 27.4).
 *
 * Yang diuji bukan "komponennya dipanggil", tapi hal yang sama dengan grafik
 * pustakawan (Task 26.4) ditambah satu kepekaan khas anggota:
 *   1. angka di layar sama dengan data di database — dan hanya milik SI PENGUJILAH
 *      yang dihitung, bukan milik anggota lain;
 *   2. dua grafik hanya tampil untuk anggota — pustakawan dan tamu tidak;
 *   3. tanpa data, halaman tetap menjawab lewat empty state.
 *
 * Tanggal diatur lewat `startOfMonth()` supaya tidak pernah jatuh di kasus
 * akhir bulan (31 Mei dikurangi satu bulan bisa meluber ke 1 Mei).
 */
class MemberDashboardReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pinjaman milik `$user` pada tanggal yang ditentukan.
     */
    private function loanAt(User $user, Carbon $borrowedAt): Loan
    {
        return Loan::factory()->create([
            'user_id' => $user->id,
            'borrowed_at' => $borrowedAt,
            'due_at' => $borrowedAt->copy()->addDays(config('perpustakaan.loan.duration_days')),
        ]);
    }

    /**
     * Riwayat baca milik `$user` yang pertama kali dibuka pada `$createdAt`
     * (`created_at` diisi lewat `forceFill` karena tidak ada di `$fillable`).
     */
    private function readingAt(User $user, Carbon $createdAt): ReadingHistory
    {
        $history = ReadingHistory::factory()->create(['user_id' => $user->id]);

        $history->forceFill(['created_at' => $createdAt])->save();

        return $history;
    }

    public function test_anggota_melihat_dua_grafik_aktivitas_pribadi(): void
    {
        $member = User::factory()->anggota()->create();
        $this->loanAt($member, now()->startOfMonth());
        $this->readingAt($member, now()->startOfMonth());

        $html = $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.my_loans_chart_title'))
            ->assertSee(__('dashboard.reading_chart_title'))
            ->getContent();

        // Dua grafik × 12 baris bulan. Jumlah ini juga yang memastikan
        // grafik benar-benar penuh (bulan kosong tetap dihitung), bukan
        // sekadar judulnya tercetak.
        $this->assertSame(24, substr_count($html, '<th scope="row">'));
    }

    public function test_total_grafik_sesuai_dengan_data_di_database(): void
    {
        $member = User::factory()->anggota()->create();

        $this->loanAt($member, now()->startOfMonth());
        $this->loanAt($member, now()->startOfMonth());
        $this->loanAt($member, now()->startOfMonth()->subMonths(3));
        // Di luar rentang 12 bulan: tidak boleh ikut mengubah total.
        $this->loanAt($member, now()->startOfMonth()->subMonths(12));

        $this->readingAt($member, now()->startOfMonth());
        $this->readingAt($member, now()->startOfMonth()->subMonths(2));
        $this->readingAt($member, now()->startOfMonth()->subMonths(12));

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
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
            )
            ->assertSee(
                __('dashboard.reading_chart_alt', [
                    'months' => 12,
                    'count' => number_format(2, 0, ',', '.'),
                ]),
                false,
            )
            ->assertSee(
                __('dashboard.reading_chart_total', ['count' => number_format(2, 0, ',', '.')]),
                false,
            );
    }

    /**
     * Grafik anggota harus terkurung ke pemiliknya. Kalau tidak, angka di
     * dasbor seseorang jadi jumlah aktivitas seluruh perpustakaan — angka
     * yang terlihat meyakinkan tapi sepenuhnya salah.
     */
    public function test_grafik_anggota_tidak_menghitung_aktivitas_anggota_lain(): void
    {
        $me = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();

        $this->loanAt($me, now()->startOfMonth());
        Loan::factory()->count(4)->create(['user_id' => $other->id]);

        $this->readingAt($me, now()->startOfMonth());
        ReadingHistory::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($me)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee(
                __('dashboard.loans_chart_alt', ['months' => 12, 'count' => '1']),
                false,
            )
            ->assertSee(
                __('dashboard.reading_chart_alt', ['months' => 12, 'count' => '1']),
                false,
            );
    }

    /**
     * Dua query grafik hanya boleh dibayar oleh anggota, dan teks "Peminjaman
     * Saya" juga menyesatkan kalau tampil di layar orang lain.
     */
    public function test_pustakawan_dan_tamu_tidak_melihat_grafik_anggota(): void
    {
        $myLoansTitle = __('dashboard.my_loans_chart_title');
        $readingTitle = __('dashboard.reading_chart_title');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee($myLoansTitle)
            ->assertDontSee($readingTitle);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($myLoansTitle)
            ->assertDontSee($readingTitle);
    }

    public function test_grafik_kosong_anggota_menampilkan_empty_state(): void
    {
        $member = User::factory()->anggota()->create();

        $html = $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            // Deskripsinya yang dipakai sebagai penanda: judulnya bisa saja
            // kebetulan sama dengan kartu riwayat membaca di bawah grafik.
            ->assertSee(__('dashboard.my_loans_chart_empty_description'))
            ->assertSee(__('dashboard.reading_chart_empty_description'))
            ->getContent();

        // Tanpa data tidak ada baris tabel — grafik tidak dipaksakan
        // dirender dengan 12 batang setinggi 0%.
        $this->assertSame(0, substr_count($html, '<th scope="row">'));
    }

    /**
     * Halaman beranda (`/`) menampilkan blok anggota yang sama; jangan sampai
     * grafiknya hanya ada di `/anggota`.
     */
    public function test_grafik_anggota_juga_tampil_di_beranda(): void
    {
        $member = User::factory()->anggota()->create();
        $this->loanAt($member, now()->startOfMonth());

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('dashboard.my_loans_chart_title'))
            ->assertSee(__('dashboard.reading_chart_title'));
    }
}
