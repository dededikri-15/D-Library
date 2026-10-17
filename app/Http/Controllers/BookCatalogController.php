<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsBookOptions;
use App\Models\Book;
use App\Models\User;
use App\Support\CatalogSearch;
use App\Support\Json;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Katalog publik & pratinjau hasil pencarian buku.
 *
 * Route: books.index (/buku), books.search (/buku/pencarian), books.show
 * (/buku/{book}). Manajemen CRUD ada di BookController; pembacaan PDF di
 * BookReaderController — satu file satu tanggung jawab.
 */
class BookCatalogController extends Controller
{
    use LoadsBookOptions;

    /**
     * Batas baris untuk endpoint pencarian hidup (Task 14.6).
     *
     * Cukup untuk menemukan satu buku yang dicari. Pratinjau yang lebih panjang
     * hanya menambah pekerjaan render di browser, dan hasil lengkapnya sudah
     * tersedia di halaman katalog biasa.
     */
    private const SEARCH_RESULT_LIMIT = 8;

    /**
     * Katalog buku publik: search, filter, sort, pagination (PRD §4 & §16).
     *
     * Route ini berada di luar group auth, jadi dipakai tamu, anggota, dan
     * staff sekaligus. Filter `status` karena itu hanya menawarkan nilai yang
     * relevan untuk pembaca (tersedia / dipinjam), bukan `inactive` — buku
     * nonaktif tidak pernah tampil di katalog publik.
     */
    public function index(Request $request): View
    {
        /*
         * Mode kelola vs mode katalog untuk staf.
         *
         * `/buku` tanpa parameter tetap halaman kelola staf (semua status +
         * aksi tambah/edit), sesuai perilaku yang sudah diuji. Tapi menu
         * "Katalog" di sidebar butuh halaman yang benar-benar berbeda,
         * bukan URL yang sama dengan menu "Kelola Buku" — kalau sama,
         * keduanya menyala bersamaan dan pengguna tidak bisa tahu mana
         * yang sedang dibuka. `?lihat=katalog` membuka tampilan katalog
         * publik: kartu, hanya buku aktif, tanpa aksi staf.
         *
         * Untuk tamu/anggota parameter ini diabaikan — mereka selalu melihat
         * katalog, jadi tidak ada mode yang perlu dipisah.
         */
        $isStaff = (bool) ($request->user()?->isStaff());
        $catalogMode = $isStaff && $request->query('lihat') === 'katalog';
        $isManagement = $isStaff && ! $catalogMode;

        /*
         * Filter katalog divalidasi. Tanpa ini `?status=ngawur` hanya
         * menghasilkan halaman kosong, dan `?q=%` membuat semua baris cocok.
         * Panjang dibatasi supaya tidak bisa mengirim query raksasa.
         *
         * `author` dan `publisher` memakai nama, bukan id. Alasannya formnya
         * memakai dropdown, jadi nilainya selalu nama yang benar-benar ada di
         * database. `Rule::exists` menutup celah kalau URL disunting manual.
         */
        $filters = $this->validFilters($request, [
            'q' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:150', Rule::exists('categories', 'slug')],
            'author' => ['nullable', 'string', 'max:150', Rule::exists('authors', 'name')],
            'publisher' => ['nullable', 'string', 'max:150', Rule::exists('publishers', 'name')],
            'status' => ['nullable', Rule::in(Book::statuses())],
        ]);

        $sort = CatalogSearch::sort($request->query('sort'));

        $books = CatalogSearch::filter(
            Book::query()
                // Katalog publik menyembunyikan buku nonaktif, tetapi staff perlu
                // melihat semua status agar buku lama tetap bisa diedit.
                ->when(! $isManagement, fn (Builder $query) => $query->where('status', '!=', Book::STATUS_INACTIVE))
                ->with(['category', 'author', 'publisher']),
            $filters
        )
            ->orderBy($sort['column'], $sort['direction'])
            // `id` sebagai penentu urutan terakhir. Tanpa ini, buku dengan
            // judul sama tidak punya urutan yang pasti, dan PostgreSQL boleh
            // mengembalikannya dalam urutan berbeda tiap kali query dijalankan.
            // Akibatnya satu buku bisa muncul di halaman 1 sekaligus halaman 2,
            // atau hilang sama sekali. Pagination butuh urutan yang total.
            ->orderBy('id')
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('books.index', [
            'books' => $books,
            ...$this->formOptions(),
            'filters' => $filters,
            'sort' => $sort['key'],
            'isManagement' => $isManagement,
            // Query yang harus ikut menempel supaya halaman tidak pindah mode
            // saat filter di-reset atau chip kategori diklik dari kartu buku.
            'modeParams' => $catalogMode ? ['lihat' => 'katalog'] : [],
        ]);
    }

    /**
     * Pencarian hidup katalog untuk pratinjau hasil (Task 14.6).
     *
     * Endpoint JSON untuk pratinjau hasil di `initSearch()`
     * (resources/js/app.js). HTML katalog lengkap sudah ada di
     * `Route::get('/buku')`; endpoint ini sengaja terpisah supaya hasil
     * pencarian tidak menarik sidebar, pagination, dan grid 12 kartu untuk
     * setiap ketikan.
     *
     * Hanya mengembalikan field yang dibutuhkan UI: judul, penulis, status, dan
     * URL detail. ISBN sengaja tidak dikirim walau pencarian memakainya,
     * karena panel pratinjau tidak menampilkannya.
     *
     * Batas `SEARCH_RESULT_LIMIT` bukan pagination, melainkan "cukup untuk
     * menemukan buku yang dicari". Hasil yang lebih banyak hanya menambah
     * pekerjaan render tanpa membantu — kalau memang tidak ada di 8 hasil
     * teratas, tekan Enter untuk halaman hasil lengkap.
     *
     * JSON dikirim dengan flag HEX_* supaya `<`, `&`, `'` dan `"` jadi escape
     * `\uXXXX`. Panel di browser aman karena JS memakai `textContent`, tapi
     * respons ini juga tidak boleh memuat tag mentah: begitu ada penyusun
     * kode yang menyalin judul ke `innerHTML` — entah untuk kebutuhan lain —
     * respons ini masih tidak menjadi celah XSS. Memperbaiki ini di server
     * lebih murah daripada memeringatkan setiap orang yang menyentuh frontend.
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $this->validFilters($request, [
            'q' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:150', Rule::exists('categories', 'slug')],
            'status' => ['nullable', Rule::in(Book::statuses())],
        ]);

        /*
         * `validFilters` membuang nilai yang gagal validasi, jadi kata kunci
         * kepanjangan akan hilang diam-diam dan endpoint ini mengembalikan 8
         * buku pertama SEBAGAI hasil pencarian. Itu menyesatkan: user mengetik
         * sesuatu yang tidak valid lalu melihat daftar yang tidak ada
         * hubungannya.
         *
         * Halaman katalog boleh begitu karena filter yang tidak valid diabaikan
         * demi user tetap melihat semua buku. Panel pratinjau tidak boleh:
         * di sini lebih baik tidak ada hasil daripada hasil yang salah.
         */
        $rawTerm = $request->query('q');
        $termFailed = filled($rawTerm) && ! array_key_exists('q', $filters);

        if ($termFailed) {
            return Json::response(['results' => []]);
        }

        $isManagement = $request->user()?->isStaff() ?? false;

        $books = CatalogSearch::filter(
            Book::query()
                ->when(! $isManagement, fn (Builder $query) => $query->where('status', '!=', Book::STATUS_INACTIVE))
                ->with(['author']),
            $filters
        )
            // Sama seperti katalog, `id` menutup urutan supaya buku dengan
            // judul sama tidak berganti posisi tiap request.
            ->orderBy('title')
            ->orderBy('id')
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get();

        return Json::response([
            'results' => $books->map(fn (Book $book) => [
                'title' => $book->title,
                'author' => $book->author?->name,
                'url' => route('books.show', $book),
                'status' => $book->status,
                'status_label' => Book::statusOptions()[$book->status] ?? $book->status,
                'is_borrowed' => ! $book->isAvailable(),
            ])->values(),
        ]);
    }

    /**
     * Detail buku (PRD §5).
     */
    public function show(Book $book): View
    {
        $book->load(['category', 'author', 'publisher'])
            ->loadCount(['copies', 'availableCopies']);

        $user = request()->user();

        return view('books.show', [
            'book' => $book,
            'readerCount' => $book->readingHistories()->count(),
            'canRead' => $book->canBeReadBy($user),
            // Status tombol "Pinjam Buku" untuk anggota (Task 10.1).
            'borrowState' => $this->borrowState($book, $user),
            // Posisi baca terakhir milik user ini, untuk tombol "Lanjut
            // membaca" (Task 11.7). Hanya untuk anggota: riwayat baca staff
            // tidak pernah ditampilkan di mana pun.
            'lastReadPage' => $user?->isMember()
                ? (int) $user->readingHistories()
                    ->where('book_id', $book->id)
                    ->value('last_page')
                : 0,
            'favoriteIds' => $user?->isMember()
                ? $user->favorites()->pluck('book_id')->all()
                : [],
            'related' => Book::query()
                ->with(['category', 'author'])
                ->where('status', '!=', Book::STATUS_INACTIVE)
                ->when(
                    $book->category_id,
                    fn ($query) => $query->where('category_id', $book->category_id)
                )
                ->whereKeyNot($book->getKey())
                ->latest('created_at')
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Keadaan tombol pinjam untuk user yang sedang membuka halaman ini.
     *
     * Ini murni untuk TAMPILAN. Penegakan aturan yang sesungguhnya ada di
     * BorrowBook, di dalam transaksi, karena ketersediaan bisa berubah
     * setelah halaman ini dirender. Nilai di sini cuma mencegah orang
     * menekan tombol yang memang tidak akan berhasil.
     *
     * @return array{can: bool, reason: ?string}
     */
    protected function borrowState(Book $book, ?User $user): array
    {
        if (! $user?->isMember()) {
            // Guest diarahkan ke login oleh middleware, staff memakai form
            // /peminjaman. Keduanya tidak punya tombol di halaman ini.
            return ['can' => false, 'reason' => null];
        }

        /*
         * Sanksi larangan meminjam didahulukan di sini: selama sanksi
         * berjalan, tidak ada gunanya menimbang status buku — tombolnya
         * tetap tidak akan lolos server (BorrowBook). Menampilkan alasan
         * sanksi lebih berguna daripada alasan "buku dipinjam lain",
         * karena yang satu bisa ditunggu, yang satu tidak.
         */
        if ($user->isSuspended()) {
            return [
                'can' => false,
                'reason' => __('messages.suspended_borrow_reason', [
                    'until' => $user->suspensionEndsAt()
                        ?->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
                        ->translatedFormat('d M Y H:i'),
                ]),
            ];
        }

        if ($book->status === Book::STATUS_INACTIVE) {
            return ['can' => false, 'reason' => __('messages.book_inactive_reason')];
        }

        $activeLoan = $book->loans()
            ->where('user_id', $user->id)
            ->active()
            ->latest('id')
            ->first();

        if ($activeLoan !== null) {
            return [
                'can' => false,
                'reason' => $activeLoan->isOverdue()
                    ? __('messages.loan_overdue_reason')
                    : __('messages.already_borrowing_reason'),
            ];
        }

        if (! $book->availableCopies()->exists()) {
            return [
                'can' => false,
                'reason' => __('messages.copy_unavailable_reason'),
            ];
        }

        return ['can' => true, 'reason' => null];
    }
}
