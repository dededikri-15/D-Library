<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Statistik peminjaman untuk grafik di dasbor pustakawan (Task 26.1).
 *
 * ## Kenapa dipisah dari HomeController?
 *
 * Dasbor pustakawan sudah memuat banyak query statistik inline di dalam
 * controller. Menambah tiga lagi di sana membuat metode `index()` tidak bisa
 * dibaca, dan angkanya mustahil diuji tanpa membuka halaman lewat HTTP.
 * Kelas ini bisa diuji sendiri: kasih data pinjaman, baca hasilnya.
 *
 * ## Kenapa pengelompokan bulan dilakukan di PHP?
 *
 * Karena dua database yang dipakai proyek ini tidak sepakat soal fungsi
 * ekstrak tanggal: PostgreSQL punya `to_char()`, SQLite punya `strftime()`
 * — tidak ada yang bisa dipakai di keduanya. Test jalan di SQLite dan
 * pengembangan di PostgreSQL, jadi SQL khusus database akan lulus di satu
 * tempat lalu gagal di tempat lain. `where('borrowed_at', '>=', ...)` +
 * penghitungan di PHP memakai bahasa yang sama untuk semua database.
 *
 * Konsekuensinya: seluruh `borrowed_at` dalam rentang ikut terbaca dari
 * database. Untuk dasbor (bukan laporan puluhan ribu baris per detik) itu
 * harga yang wajar, dan lebih murah daripada dua cabang SQL yang harus
 * dijaga kembar hasilnya.
 */
class LoanReport
{
    /**
     * Jumlah peminjaman per bulan untuk `$months` bulan terakhir.
     *
     * Bulan yang kosong tetap dikembalikan dengan nilai 0. Tanpa ini sumbu X
     * grafik akan melompat melewati bulan tanpa peminjaman, dan pembaca bisa
     * salah mengira peminjaman bulan Maret terjadi di bulan April.
     *
     * Tanggal acuan `borrowed_at` (saat buku dipinjam), bukan `created_at`
     * (saat baris dibuat di database) dan bukan `returned_at`. Peminjaman yang
     * terjadi bulan lalu lalu dikembalikan minggu ini tetap dihitung bulan lalu.
     *
     * @return Collection<int, array{key: string, short: string, label: string, value: int}>
     *                                                                                       urut kronologis, bulan ini di posisi terakhir
     */
    public function perMonth(int $months = 12): Collection
    {
        $months = max(1, $months);
        $start = now()->startOfMonth()->subMonths($months - 1);

        $counts = Loan::query()
            ->whereNotNull('borrowed_at')
            ->where('borrowed_at', '>=', $start)
            ->pluck('borrowed_at')
            ->countBy(fn ($borrowedAt) => $borrowedAt->format('Y-m'));

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

    /**
     * Buku yang paling sering dipinjam, dari yang paling sering.
     *
     * Buku tanpa peminjaman dibuang setelah `limit()`, bukan lewat `HAVING`:
     * `COUNT(...) > 0` boleh ditulis berbeda-beda caranya di SQLite dan
     * PostgreSQL, sedangkan menyaring di PHP hasilnya sama persis dan tidak
     * menambah cabang SQL. (`limit()` tetap dipakai lebih dulu supaya buku
     * tanpa pinjaman — yang selalu urutan paling bawah — ikut terpotong,
     * lalu sisa nol dibersihkan di sini.)
     *
     * @return Collection<int, Book>
     */
    public function topBooks(int $limit = 5): Collection
    {
        return Book::query()
            ->with(['author', 'category'])
            ->withCount('loans')
            ->orderByDesc('loans_count')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get()
            ->filter(fn (Book $book) => $book->loans_count > 0)
            ->values();
    }

    /**
     * Anggota yang paling banyak meminjam, dari yang paling banyak.
     *
     * Hanya `role = anggota` karena memang satu-satunya role yang bisa
     * meminjam: route `books.borrow` berada di group `role:anggota`.
     * Menghitung pustakawan akan menampilkan orang yang tidak pernah bisa
     * meminjam lewat aplikasi.
     *
     * `withCount('loans')` menghitung SEMUA pinjaman, termasuk yang belum
     * dikembalikan — angka ini berarti "seberapa aktif", bukan "seberapa
     * sering selesai".
     *
     * @return Collection<int, User>
     */
    public function topBorrowers(int $limit = 5): Collection
    {
        return User::query()
            ->where('role', User::ROLE_ANGGOTA)
            ->withCount('loans')
            ->orderByDesc('loans_count')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get()
            ->filter(fn (User $user) => $user->loans_count > 0)
            ->values();
    }
}
