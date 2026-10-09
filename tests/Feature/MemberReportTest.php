<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Support\MemberReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Uji sumber data grafik dasbor anggota (Task 27.1).
 *
 * Yang diuji bukan "query-nya jalan", tapi keputusan yang gampang salah:
 *
 * 1. Hitungan harus terkurung ke satu user — dua anggota tidak boleh
 *    menghitung milik satu sama lain.
 * 2. Bulan kosong harus tetap muncul dengan 0 supaya sumbu X tidak melompat.
 * 3. Grafik membaca harus memakai `created_at` (saat buku PERTAMA dibuka),
 *    bukan `last_read_at` yang ditimpa tiap kali buku yang sama dibuka lagi —
 *    kalau salah, bulan lalu bisa jadi 0 hanya karena bukunya dibaca ulang.
 *
 * Semua tanggal eksplisit lewat `startOfMonth()` supaya tidak pernah jatuh di
 * kasus akhir bulan (31 Mei dikurangi satu bulan bisa meluber ke 1 Mei).
 */
class MemberReportTest extends TestCase
{
    use RefreshDatabase;

    private MemberReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report = new MemberReport;
    }

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
     * Riwayat baca milik `$user` yang pertama kali dibuka pada `$createdAt`.
     *
     * `created_at` diisi lewat `forceFill` karena tidak ada di `$fillable` —
     * waktu pembuatan baris harus bisa ditentukan test (kalau ditulis saat
     * test berjalan, hitungannya selalu jatuh di bulan ini dan uji di bawah
     * tidak akan pernah membedakan yang benar dari yang salah).
     */
    private function readingAt(User $user, Carbon $createdAt): ReadingHistory
    {
        $history = ReadingHistory::factory()->create([
            'user_id' => $user->id,
            'last_read_at' => $createdAt,
        ]);

        $history->forceFill(['created_at' => $createdAt])->save();

        return $history;
    }

    public function test_loans_per_month_hanya_menghitung_pinjaman_user_itu(): void
    {
        $me = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();

        $this->loanAt($me, now()->startOfMonth());
        $this->loanAt($me, now()->startOfMonth());
        $this->loanAt($other, now()->startOfMonth());

        $items = $this->report->loansPerMonth($me);

        $this->assertCount(12, $items);
        $this->assertSame(2, $items->last()['value']);
        $this->assertSame(2, $items->sum('value'), 'Pinjaman anggota lain tidak boleh ikut terhitung.');
    }

    public function test_loans_per_month_bulan_kosong_nol_dan_urut_kronologis(): void
    {
        $me = User::factory()->anggota()->create();
        $this->loanAt($me, now()->startOfMonth()->subMonths(2));
        // Di luar rentang 12 bulan: tidak boleh ikut terhitung.
        $this->loanAt($me, now()->startOfMonth()->subMonths(12));

        $items = $this->report->loansPerMonth($me);

        $this->assertCount(12, $items);
        $this->assertSame(now()->startOfMonth()->format('Y-m'), $items->last()['key']);
        $this->assertSame(now()->startOfMonth()->subMonths(11)->format('Y-m'), $items->first()['key']);
        $this->assertSame(1, $items->sum('value'));

        $keys = $items->pluck('key')->all();
        $sorted = $keys;
        sort($sorted);

        $this->assertSame($sorted, $keys, 'Bulan harus urut kronologis dari yang paling lama.');
        $this->assertSame(0, $items->first()['value'], 'Bulan tanpa pinjaman harus tetap tampil dengan 0.');
    }

    public function test_grafik_membaca_memakai_waktu_pertama_kali_dibaca(): void
    {
        $me = User::factory()->anggota()->create();
        $twoMonthsAgo = now()->startOfMonth()->subMonths(2);

        $history = $this->readingAt($me, $twoMonthsAgo);
        // Dibuka lagi bulan ini: `last_read_at` bergeser, `created_at` tidak.
        $history->update(['last_read_at' => now(), 'last_page' => 50]);

        $items = $this->report->readingStartsPerMonth($me)->keyBy('key');

        $this->assertSame(1, $items->get($twoMonthsAgo->format('Y-m'))['value']);
        $this->assertSame(0, $items->get(now()->startOfMonth()->format('Y-m'))['value']);
    }

    public function test_grafik_membaca_hanya_menghitung_buku_user_itu(): void
    {
        $me = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();

        $this->readingAt($me, now()->startOfMonth());
        $this->readingAt($other, now()->startOfMonth());
        $this->readingAt($other, now()->startOfMonth());

        $items = $this->report->readingStartsPerMonth($me);

        $this->assertSame(1, $items->last()['value']);
        $this->assertSame(1, $items->sum('value'));
    }

    public function test_kedua_grafik_kosong_ketika_belum_ada_aktivitas(): void
    {
        $me = User::factory()->anggota()->create();

        $loans = $this->report->loansPerMonth($me);
        $readings = $this->report->readingStartsPerMonth($me);

        $this->assertCount(12, $loans);
        $this->assertCount(12, $readings);
        $this->assertSame(0, $loans->sum('value'));
        $this->assertSame(0, $readings->sum('value'));
    }

    public function test_jumlah_bulan_bisa_dikurangi(): void
    {
        $me = User::factory()->anggota()->create();
        $this->loanAt($me, now()->startOfMonth()->subMonths(5));

        $this->assertCount(3, $this->report->loansPerMonth($me, 3));
        $this->assertCount(3, $this->report->readingStartsPerMonth($me, 3));
        $this->assertSame(0, $this->report->loansPerMonth($me, 3)->sum('value'));

        // Nilai 0 atau negatif tidak boleh membuat deretnya kosong.
        $this->assertCount(1, $this->report->loansPerMonth($me, 0));
    }
}
