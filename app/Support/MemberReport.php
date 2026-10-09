<?php

namespace App\Support;

use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Statistik pribadi anggota untuk grafik di dasbor anggota (Task 27.1).
 *
 * Keduanya dikerjakan di PHP lewat `MonthlySeries` supaya bulan kosong tetap
 * tampil, dan memakai dua kolom tanggal berbeda yang punya arti berbeda —
 * penjelasannya ada di tiap metode.
 *
 * Kenapa terpisah dari `LoanReport`?
 *
 * `LoanReport` menghitung seluruh pustaka (tanpa filter user) untuk kebutuhan
 * laporan pustakawan. Metode di sini selalu dibatasi satu user, jadi query-nya
 * berbeda, mesininya (`MonthlySeries`) sama.
 */
class MemberReport
{
    /**
     * Jumlah pinjaman ANGGOTA INI per bulan selama `$months` bulan terakhir.
     *
     * Tanggal acuan `borrowed_at` — sama dengan grafik pustakawan — supaya
     * peminjaman bulan lalu dihitung di bulan lalu walaupun bukunya baru
     * dikembalikan minggu ini.
     *
     * @return Collection<int, array{key: string, short: string, label: string, value: int}>
     *                                                                                       urut kronologis, bulan ini di posisi terakhir
     */
    public function loansPerMonth(User $user, int $months = 12): Collection
    {
        [$start, $months] = $this->range($months);

        $counts = Loan::query()
            ->where('user_id', $user->id)
            ->whereNotNull('borrowed_at')
            ->where('borrowed_at', '>=', $start)
            ->pluck('borrowed_at')
            ->countBy(fn ($borrowedAt) => $borrowedAt->format('Y-m'));

        return MonthlySeries::build($start, $months, $counts);
    }

    /**
     * Jumlah buku yang ANGGOTA INI mulai baca per bulan selama `$months` bulan.
     *
     * Kenapa `created_at`, bukan `last_read_at`?
     *
     * Tabel `reading_histories` hanya punya SATU baris per (user, book) —
     * kolom `last_page` dan `last_read_at` ditimpa setiap kali buku yang
     * sama dibuka lagi. Kalau dipakai sebagai acuan bulan, buku yang dibaca
     * Maret lalu dibaca lagi bulan ini akan pindah hitungannya ke bulan ini,
     * dan Maret tampil 0 walaupun anggotanya memang membaca waktu itu.
     *
     * `created_at` adalah saat buku itu PERTAMA kali dibuka: peristiwa sekali
     * seumur buku, jadi hitungannya stabil dan jumlahnya benar — grafiknya
     * berarti "seberapa rajin anggota mulai buku baru", yang memang ingin
     * dilihat di dasbor.
     *
     * @return Collection<int, array{key: string, short: string, label: string, value: int}>
     *                                                                                       urut kronologis, bulan ini di posisi terakhir
     */
    public function readingStartsPerMonth(User $user, int $months = 12): Collection
    {
        [$start, $months] = $this->range($months);

        $counts = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn ($createdAt) => $createdAt->format('Y-m'));

        return MonthlySeries::build($start, $months, $counts);
    }

    /**
     * Titik awal rentang grafik. `startOfMonth()` dipakai sebelum dikurangi
     * supaya akhir bulan tidak meluber (31 Mei dikurangi 1 bulan bisa jadi
     * 1 Mei, bukan 1 April).
     *
     * @return array{0: CarbonInterface, 1: int}
     */
    private function range(int $months): array
    {
        $months = max(1, $months);

        return [now()->startOfMonth()->subMonths($months - 1), $months];
    }
}
