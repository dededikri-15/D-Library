<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat setiap pembukaan halaman yang dipantau ke tabel `page_views`.
 *
 * Daftarnya mengikuti MENU yang dilihat pembaca (lihat sidebar): beranda,
 * katalog, kategori, detail buku, riwayat pinjam, favorit, riwayat baca,
 * dan kartu perpustakaan. Halaman login dan SELURUH halaman staf (data
 * peminjaman, kelola buku, laporan, dst.) sengaja dilewati — pustakawan
 * tidak perlu menghitung berapa kali dia sendiri membuka panel admin.
 *
 * Halaman member hanya dicatat bila penggunanya SUDAH login. Middleware ini
 * berjalan di akhir grup `web`, SEBELUM middleware `auth` route; tanpa cek
 * itu, tamu yang membuka /favorit akan ikut terhitung padahal nanti cuma
 * dilempar ke halaman login.
 *
 * ## Kenapa dicatat SEBELUM `$next`, bukan sesudahnya?
 *
 * Karena view dirender di dalam `$next` (controller mengembalikan View,
 * lalu `Router::toResponse` me-render-nya sebelum middleware sempat
 * melanjutkan). Kalau pencatatan dipindah ke sesudah `$next`, angka yang
 * tampil di footer selalu ketinggalan satu kunjungan — dan pengunjung
 * pertama bahkan melihat "Total kunjungan 0" padahal dia sedang berdiri
 * di halaman itu.
 *
 * Konsekuensinya: /buku/999999 (id numerik yang tidak ada) ikut terhitung
 * walau berakhir 404, karena status respons belum bisa diketahui pada
 * saat ini. Pengecualian itu disengaja — biaya menghitung satu request
 * gagal jauh lebih kecil daripada menampilkan angka yang menipu. Request
 * dengan id non-numerik (/buku/abc) tidak match rute sama sekali, jadi
 * namanya null dan tidak pernah tercatat.
 *
 * `upsert` dipakai karena tabel ini punya satu baris per rute: baris baru
 * disisipkan kalau belum ada, dan `view_count` dinaikkan lewat ekspresi
 * mentah kalau sudah ada. Ekspresinya harus ditulis `page_views.view_count + 1`
 * (dengan kualifikasi nama tabel) — lihat catatan di `record()` mengenai
 * kolom ambigu di `ON CONFLICT` PostgreSQL yang lolos dari suite SQLite.
 */
class CountPublicPageView
{
    /**
     * Rute publik: siapa pun (termasuk tamu) boleh membukanya, jadi selalu
     * dicatat.
     *
     * @var list<string>
     */
    private const TRACKED_ROUTES = [
        'home',
        'books.index',
        'categories.public',
        'books.show',
    ];

    /**
     * Halaman di menu member (Riwayat Pinjam, Favorit, Riwayat Baca, Kartu
     * Perpustakaan). Dicatat hanya bila penggunanya sudah login — lihat
     * catatan di docblock kelas.
     *
     * @var list<string>
     */
    private const MEMBER_TRACKED_ROUTES = [
        'loans.mine',
        'favorites.index',
        'reading-histories.index',
        'library-cards.show',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCount($request)) {
            $this->record((string) $request->route()?->getName());
        }

        return $next($request);
    }

    private function shouldCount(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $name = $request->route()?->getName();

        if ($name === null) {
            return false;
        }

        if (in_array($name, self::TRACKED_ROUTES, true)) {
            return true;
        }

        return in_array($name, self::MEMBER_TRACKED_ROUTES, true)
            && $request->user() !== null;
    }

    private function record(string $routeName): void
    {
        $now = now();

        DB::table('page_views')->upsert(
            [[
                'route_name' => $routeName,
                'view_count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['route_name'],
            [
                // Kualifikasi `page_views.` WAJIB: di dalam `ON CONFLICT DO
                // UPDATE`, nama kolom tanpa prefix ambigu di PostgreSQL — bisa
                // merujuk baris lama maupun baris yang hendak disisipkan — dan
                // dia akan melempar SQLSTATE 42702. SQLite justru menerima versi
                // polos tanpa komplain, jadi suite test (sqlite :memory:) tidak
                // pernah menangkap kesalahan ini. Hanya smoke test ke
                // PostgreSQL yang membongkarnya (pelajaran AGENTS.md).
                'view_count' => DB::raw('page_views.view_count + 1'),
                'updated_at' => $now,
            ]
        );
    }
}
