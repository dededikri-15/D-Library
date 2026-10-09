<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Deret bulanan seragam untuk semua grafik batang di dasbor (Task 26 & 27).
 *
 * Kenapa dipisah dari `LoanReport`?
 *
 * Dasbor pustakawan dan dasbor anggota sama-sama butuh 12 titik data yang
 * bulan kosongnya tetap muncul dengan nilai 0. Tanpa nilai 0 itu sumbu X akan
 * melompat melewati bulan sepi dan pembaca bisa salah membaca bulan. Dua kelas
 * yang menyalin satu sama lain loop-nya berarti dua tempat untuk lupa
 * memperbaiki bug yang sama, jadi logikanya ditulis sekali di sini.
 *
 * Pengelompokan bulannya sendiri tetap dilakukan pemanggil (di PHP, bukan SQL)
 * karena `to_char()` hanya ada di PostgreSQL dan `strftime()` hanya ada di
 * SQLite — lihat catatan panjang di `LoanReport`.
 */
class MonthlySeries
{
    /**
     * Susun hitungan per bulan menjadi deret berurutan untuk grafik.
     *
     * @param  CarbonInterface  $start  bulan pertama, sudah `startOfMonth()`
     * @param  int  $months  jumlah bulan yang harus muncul (termasuk `$start`)
     * @param  Collection<string, int>  $counts  jumlah per kunci bulan 'Y-m'
     *                                           bulan tanpa hitungan tetap muncul dengan nilai 0
     * @return Collection<int, array{key: string, short: string, label: string, value: int}>
     *                                                                                       urut kronologis, bulan terkini di posisi terakhir
     */
    public static function build(CarbonInterface $start, int $months, Collection $counts): Collection
    {
        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $counts) {
                $date = $start->copy()->addMonths($offset);
                $key = $date->format('Y-m');

                return [
                    'key' => $key,
                    'short' => $date->format('M'),
                    'label' => $date->format('M Y'),
                    'value' => (int) $counts->get($key, 0),
                ];
            })
            ->values();
    }
}
