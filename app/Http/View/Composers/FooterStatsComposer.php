<?php

namespace App\Http\View\Composers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Menyuntikkan statistik kunjungan ke footer.
 *
 * Query-nya sengaja TIDAK di-cache: tabel `page_views` berisi paling banyak
 * beberapa baris (satu per rute publik), jadi `SELECT route_name, view_count`
 * selesai dalam mikrodetik. Menambah lapisan cache hanya memperkenalkan
 * masalah baru — angka yang tertinggal beberapa detik di belakang
 * kenyataan, plus keharusan mengingat kapan harus membuang cache.
 *
 * Karena middleware pencatat berjalan SEBELUM view dirender, angka yang
 * tampil sudah termasuk kunjungan orang yang sedang melihat halaman itu.
 */
class FooterStatsComposer
{
    public function compose(View $view): void
    {
        $counts = DB::table('page_views')->pluck('view_count', 'route_name');

        $routeName = request()->route()?->getName();

        $view->with('pageViewStats', [
            'total' => (int) $counts->sum(),
            // null artinya halaman ini tidak dipantau (login, dasbor, dst.)
            // — footer hanya menampilkan total situs, bukan angka halaman.
            'current' => isset($counts[$routeName]) ? (int) $counts[$routeName] : null,
        ]);
    }
}
