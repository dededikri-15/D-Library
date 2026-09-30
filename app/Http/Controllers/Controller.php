<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithFlash;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

abstract class Controller
{
    // Dipakai di sini, bukan di tiap controller anak, karena helper bersama
    // (`masterIndex`, `destroyMasterData`) memanggil `success()`/`failure()`.
    // Kalau trait-nya hanya menempel di kelas anak, pemanggilan dari kelas
    // induk terlihat seperti method yang tidak ada.
    use RespondsWithFlash;

    /**
     * Ambil nilai filter query yang valid, abaikan yang tidak valid.
     *
     * Filter di URL sering diketik atau diutak-atik orang. Kalau nilai buruk
     * langsung dibalas 422/redirect, orang yang salah ketik akan
     * mendapat halaman kosong tanpa penjelasan. Jadi di sini nilai yang
     * tidak lolos validasi simply diabaikan, dan halaman tetap tampil.
     *
     * Yang penting: nilainya sudah jadi tipe yang benar (mis. `user_id`
     * menjadi integer), sehingga tidak pernah sampai ke SQL sebagai teks.
     */
    protected function validFilters(Request $request, array $rules): array
    {
        $validator = Validator::make($request->query(), $rules);

        // PENTING: pakai `valid()`, bukan `validated()`. Method `validated()`
        // melempar ValidationException kalau ada yang gagal, yang akan membuat
        // halaman daftar memantul dengan 302. `valid()` diam-diam mengembalikan
        // hanya data yang lolos.
        $failed = $validator->errors()->keys();
        $valid = $validator->valid();

        $filters = [];

        foreach (array_keys($rules) as $key) {
            if (in_array($key, $failed, true)) {
                continue;
            }

            $value = $valid[$key] ?? null;

            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    /**
     * Hilangkan arti khusus karakter wildcard SQL dari input pencarian.
     *
     * Tanpa ini, mengetik `%` di kotak pencarian akan membuat semua baris
     * cocok (persis seperti `LIKE '%%'`). Tanda `%` dan `_` di-escape, lalu
     * `\` ditambahkan sebagai escape character supaya kisahnya jelas.
     */
    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Terapkan pencarian sederhana pada satu kolom teks.
     *
     * Harus dipakai di controller yang sudah divalidasi lebih dulu.
     */
    protected function applySearch(Builder $query, string $column, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->whereRaw(
            "LOWER({$column}) LIKE ? ESCAPE '\\'",
            ['%'.$this->escapeLike(mb_strtolower($term)).'%']
        );
    }

    /**
     * Halaman daftar untuk master data sederhana.
     *
     * Kategori, penulis, dan penerbit punya bentuk yang persis sama: satu
     * kotak cari di kolom `name`, urutan `name`, dan jumlah buku per baris.
     * Setelah ditulis tiga kali, ketiganya mulai berbeda tipis - satu lupa
     * `withQueryString()` sehingga filter hilang saat pindah halaman, satu
     * berubah urutan. Satu implementasi membuat ketiganya pasti sama.
     *
     * Nama variabel hasil tetap diteruskan apa adanya karena setiap view
     * memang memakainya dengan nama sendiri (`$categories`, `$authors`,
     * `$publishers`).
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function masterIndex(Request $request, string $modelClass, string $view, string $variable): View
    {
        $filters = $this->validFilters($request, ['q' => ['nullable', 'string', 'max:150']]);

        $rows = $modelClass::query()
            ->withCount('books')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $this->applySearch($query, 'name', $q))
            ->orderBy('name')
            ->paginate(config('perpustakaan.pagination.per_page'))
            // Tanpa ini, filter di URL hilang begitu user menekan halaman 2.
            ->withQueryString();

        return view($view, [$variable => $rows]);
    }

    /**
     * Hapus satu baris master data, dengan pengaman jumlah buku.
     *
     * Kenapa books_category_id (dan dua kolom lain di tabel `books`) memakai
     * `cascadeOnDelete()`: books dihapus dari katalog, dan ikutannya borrower
     * yang punya loan/favorit untuk buku itu ikut hilang. Itu benar untuk
     * hapus buku, tapi untuk hapus kategori/penulis/penerbit efeknya jauh
     * lebih besar dari yang kelihatan di dialog konfirmasi — satu kategori
     * bisa menghapus puluhan buku beserta riwayat pinjamannya.
     *
     * Karena itu hapus master data di sini DITOLAK selama masih ada buku yang
     * memakainya, dan user diberi tahu berapa banyak. Pengaman di level
     * aplikasi, bukan constraint: pesan yang muncul bisaittening dibaca,
     * sedangkan `QueryException` dari foreign key hanya jadi 500.
     *
     * Sisanya: pengecekan jumlah buku dan penghapusan sengaja tidak
     * di(transaction)-kan. Keduanya harus konsisten dengan apa yang dilihat
     * user di form edit (yang menampilkan jumlah buku yang sama), dan selisih
     * sesaat antara "dicek 0 buku" dan "dihapus" hanya bisa terjadi kalau ada
     * dua pustakawan yang menekan hapus pada milidetik yang sama.
     *
     * `$beforeDelete` hanya dipanggil kalau data benar-benar dihapus. Penulis
     * butuh itu untuk berkas fotonya: tanpa penjaga, tries yang ditolak
     * tetap menghapus foto sementara penulisnya masih ada di database.
     *
     * @param  string  $label  nama jenis data dalam kalimat, mis. "kategori"
     */
    protected function destroyMasterData(
        Model $row,
        string $label,
        string $indexRoute,
        ?Closure $beforeDelete = null,
    ): RedirectResponse {
        $booksCount = $row->books()->count();

        if ($booksCount > 0) {
            return $this->failure(
                $indexRoute,
                "{$label} tidak bisa dihapus: masih dipakai {$booksCount} buku. "
                ."Pindahkan dulu buku-buku itu ke {$label} lain.",
            );
        }

        $beforeDelete?->__invoke();

        $row->delete();

        return $this->success($indexRoute, ucfirst($label).' berhasil dihapus.');
    }

    /**
     * Normalisasi ISBN: sisip hanya digit dan huruf X.
     *
     * Huruf X ikut karena ISBN-10 boleh berakhiran X, jadi `0-8044-2957-X`
     * adalah ISBN yang valid.
     */
    protected function isbnDigits(string $value): string
    {
        return preg_replace('/[^0-9Xx]/', '', $value) ?? '';
    }

    /**
     * Cari ISBN dengan mengabaikan tanda penghubung, di kolom dan di input.
     *
     * ISBN tersimpan bergaris, misalnya `978-602-001-001-1`. Orang lebih
     * biasa mengetik `9786020010011` atau sebagian saja seperti `978-602`.
     * Kalau yang diketik dibandingkan mentah-mentah dengan yang disimpan,
     * dua dari tiga gaya ketik itu gagal. Jadi keduanya dibersihkan dulu:
     * kolom dan input dibuang semua karakter kecuali digit dan X.
     *
     * Catatan performa: membungkus kolom dengan fungsi membuat index pada
     * `books.isbn` tidak terpakai. Tidak masalah di sini karena seluruh
     * pencarian katalog sudah memakai `LIKE %...%` yang memang tidak bisa
     * memakai index btree. Lihat catatan Task 9.9.
     *
     * Parameter `$boolean` penting. Kalau pemanggil membungkus pemanggilan ini
     * di dalam `where(function ($group) { ... })` bersama beberapa `orWhere`,
     * pemanggilan ini harus memakai `orWhereRaw`. Tanpa itu Eloquent menambahnya
     * dengan `AND`, dan hasilnya "judul cocok DAN ISBN cocok" — artinya
     * pencarian ISBN tidak akan pernah menemukan apa pun.
     */
    protected function applyIsbnSearch(Builder $query, ?string $term, string $boolean = 'and'): Builder
    {
        $digits = $this->isbnDigits((string) $term);

        // Kalau user hanya mengetik tanda baca, sisanya kosong. Pola kosong
        // akan membuat semua baris cocok, persis bug yang sudah pernah
        // terjadi di kotak pencarian (lihat escapeLike).
        if ($digits === '') {
            return $query;
        }

        return $query->whereRaw(
            "LOWER(REPLACE(REPLACE(isbn, '-', ''), ' ', '')) LIKE ? ESCAPE '\\'",
            ['%'.$this->escapeLike(mb_strtolower($digits)).'%'],
            $boolean
        );
    }
}
