<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Logika query katalog buku: pencarian gabungan, filter, dan urutan.
 *
 * Dipisah dari controller supaya mode katalog publik, mode kelola staf,
 * dan endpoint pratinjau JSON memakai perilaku yang persis sama tanpa
 * controller saling mewarisi kode (lihat BookCatalogController dan
 * BookController).
 */
class CatalogSearch
{
    /**
     * Hilangkan arti khusus karakter wildcard SQL dari input pencarian.
     *
     * Tanpa ini, mengetik `%` di kotak pencarian akan membuat semua baris
     * cocok (persis seperti `LIKE '%%'`). Tanda `%` dan `_` di-escape, lalu
     * `\` ditambahkan sebagai escape character supaya kisahnya jelas.
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    /**
     * Terapkan filter katalog yang sudah lolos `validFilters()` (PRD §4).
     *
     * Controller bertugas memvalidasi; di sini filter hanya diterjemahkan
     * menjadi kondisi query. Kunci yang tidak ada di `$filters` dilewati,
     * jadi endpoint pratinjau (hanya q/category/status) dan halaman katalog
     * (semua filter) berbagi kode yang sama tanpa perilaku berbeda.
     */
    public static function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($query, $q) => self::apply($query, $q))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas(
                'category',
                fn ($cat) => $cat->where('slug', $slug)
            ))
            ->when($filters['author'] ?? null, fn ($query, $name) => $query->whereHas(
                'author',
                fn ($author) => $author->where('name', $name)
            ))
            ->when($filters['publisher'] ?? null, fn ($query, $name) => $query->whereHas(
                'publisher',
                fn ($publisher) => $publisher->where('name', $name)
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    /**
     * Pencarian terpadu katalog (PRD §16).
     *
     * Satu kata kunci dicocokkan ke lima hal sekaligus: judul, ISBN, penulis,
     * kategori, dan penerbit. Jadi mengetik "Andi" menemukan buku tulisan Andi
     * meskipun judulnya tidak mengandung "Andi", dan mengetik ISBN menemukan
     * buku yang judulnya sama sekali tidak menyerupai angka tersebut.
     *
     * Kelima kondisi itu disambung dengan OR dan dibungkus satu closure.
     * Closure itu wajib: tanpa itu, `orWhereHas` di sini akan menimpa filter
     * lain yang ditambahkan setelahnya, dan katalog hanya menampilkan
     * "buku yang statusnya cocok ATAU yang penulisnya cocok" — kategori,
     * penerbit, dan pagination ikut kacau.
     */
    public static function apply(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.self::escapeLike(mb_strtolower($term)).'%';

        return $query->where(function (Builder $group) use ($like, $term) {
            $group->whereRaw("LOWER(title) LIKE ? ESCAPE '\\'", [$like])
                ->orWhereHas('author', fn ($author) => $author->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]))
                ->orWhereHas('category', fn ($category) => $category->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]))
                ->orWhereHas('publisher', fn ($publisher) => $publisher->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]));

            // ISBN perlu normalisasi tanda penghubung sebelum dibandingkan,
            // karena yang tersimpan bergaris tapi yang diketik orang kemungkinan
            // tidak. Lihat isIsbn() di kelas ini.
            //
            // WAJIB memakai `or`. Tanpa itu kondisi ini dirangkai dengan AND,
            // sehingga syarat ISBN harus terpenuh sekaligus dengan syarat
            // judul/penulis/kategori/penerbit, dan buku yang dicari lewat ISBN
            // saja tidak akan pernah muncul. Closure group di atas hanya
            // berfungsi mengelompokkan; operator di dalam group tetap
            // menentukan.
            self::isIsbn($group, $term, 'or');
        });
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
     * Parameter `$boolean` penting. Kalau pemanggil membungkus pemanggilan ini
     * di dalam `where(function ($group) { ... })` bersama beberapa `orWhere`,
     * pemanggilan ini harus memakai `orWhereRaw`. Tanpa itu Eloquent menambahnya
     * dengan `AND`, dan hasilnya "judul cocok DAN ISBN cocok" — artinya
     * pencarian ISBN tidak akan pernah menemukan apa pun.
     */
    public static function isIsbn(Builder $query, ?string $term, string $boolean = 'and'): Builder
    {
        $digits = self::isbnDigits((string) $term);

        // Kalau user hanya mengetik tanda baca, sisanya kosong. Pola kosong
        // akan membuat semua baris cocok, persis bug yang sudah pernah
        // terjadi di kotak pencarian (lihat escapeLike).
        if ($digits === '') {
            return $query;
        }

        return $query->whereRaw(
            "LOWER(REPLACE(REPLACE(isbn, '-', ''), ' ', '')) LIKE ? ESCAPE '\\'",
            ['%'.self::escapeLike(mb_strtolower($digits)).'%'],
            $boolean
        );
    }

    /**
     * Normalisasi ISBN: sisip hanya digit dan huruf X.
     *
     * Huruf X ikut karena ISBN-10 boleh berakhiran X, jadi `0-8044-2957-X`
     * adalah ISBN yang valid.
     */
    private static function isbnDigits(string $value): string
    {
        return preg_replace('/[^0-9Xx]/', '', $value) ?? '';
    }

    /**
     * Terima hanya nama kolom & arah yang diizinkan, agar request tidak bisa
     * menyuntikkan kolom acak ke orderBy.
     *
     * @return array{column: string, direction: string, key: string}
     */
    public static function sort(?string $key): array
    {
        return match ($key) {
            'title' => ['column' => 'title', 'direction' => 'asc', 'key' => 'title'],
            'oldest' => ['column' => 'publication_year', 'direction' => 'asc', 'key' => 'oldest'],
            'latest' => ['column' => 'created_at', 'direction' => 'desc', 'key' => 'latest'],
            default => ['column' => 'created_at', 'direction' => 'desc', 'key' => 'latest'],
        };
    }
}
