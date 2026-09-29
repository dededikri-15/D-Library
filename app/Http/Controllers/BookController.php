<?php

namespace App\Http\Controllers;

use App\Actions\RecordReading;
use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Http\Requests\BookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Support\Json;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    use HandlesUploads;
    use RespondsWithFlash;

    /**
     * Batas baris untuk endpoint pencarian hidup (Task 14.6).
     *
     * Cukup untuk menemukan satu buku yang dicari. Pratinjau yang lebih panjang
     * hanya menambah pekerjaan render di browser, dan hasil lengkapnya sudah
     * tersedia di halaman katalog biasa.
     */
    private const SEARCH_RESULT_LIMIT = 8;

    public function __construct(protected RecordReading $recordReading)
    {
        //
    }

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
        $isManagement = $request->user()?->isStaff() ?? false;

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

        $sort = $this->resolveSort($request->query('sort'));

        $books = Book::query()
            // Katalog publik menyembunyikan buku nonaktif, tetapi staff perlu
            // melihat semua status agar buku lama tetap bisa diedit.
            ->when(! $isManagement, fn (Builder $query) => $query->where('status', '!=', Book::STATUS_INACTIVE))
            ->with(['category', 'author', 'publisher'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $this->applyCatalogSearch($query, $q))
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
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
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

        $books = Book::query()
            ->when(! $isManagement, fn (Builder $query) => $query->where('status', '!=', Book::STATUS_INACTIVE))
            ->with(['author'])
            ->when($filters['q'] ?? null, fn ($query, $q) => $this->applyCatalogSearch($query, $q))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas(
                'category',
                fn ($cat) => $cat->where('slug', $slug)
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
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
    protected function applyCatalogSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$this->escapeLike(mb_strtolower($term)).'%';

        return $query->where(function (Builder $group) use ($like, $term) {
            $group->whereRaw("LOWER(title) LIKE ? ESCAPE '\\'", [$like])
                ->orWhereHas('author', fn ($author) => $author->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]))
                ->orWhereHas('category', fn ($category) => $category->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]))
                ->orWhereHas('publisher', fn ($publisher) => $publisher->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$like]));

            // ISBN perlu normalisasi tanda penghubung sebelum dibandingkan,
            // karena yang tersimpan bergaris tapi yang diketik orang kemungkinan
            // tidak. Lihat applyIsbnSearch di Controller.php.
            //
            // WAJIB memakai `or`. Tanpa itu kondisi ini dirangkai dengan AND,
            // sehingga syarat ISBN harus terpenuh sekaligus dengan syarat
            // judul/penulis/kategori/penerbit, dan buku yang dicari lewat ISBN
            // saja tidak akan pernah muncul. Closure group di atas hanya
            // berfungsi mengelompokkan; operator di dalam group tetap
            // menentukan.
            $this->applyIsbnSearch($group, $term, 'or');
        });
    }

    /**
     * Detail buku (PRD §5).
     */
    public function show(Book $book): View
    {
        $book->load(['category', 'author', 'publisher']);

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

        if ($book->status === Book::STATUS_INACTIVE) {
            return ['can' => false, 'reason' => 'Buku ini sedang tidak aktif.'];
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
                    ? 'Peminjamanmu sudah lewat jatuh tempo.'
                    : 'Kamu sedang meminjam buku ini.',
            ];
        }

        if (! $book->isAvailable()) {
            return ['can' => false, 'reason' => 'Buku ini sedang dipinjam anggota lain.'];
        }

        return ['can' => true, 'reason' => null];
    }

    /**
     * Halaman pembaca digital (PRD §10, Task 11.1 & 11.2).
     *
     * Berkas PDF sendiri TIDAK disimpan di disk publik. Halaman ini hanya
     * menampilkan objek yang menunjuk ke route `books.file`, dan route itulah
     * yang memeriksa hak akses lalu me-stream berkasnya.
     *
     * Membuka halaman ini dicatat sebagai kunjungan (lihat RecordReading).
     * Yang dicatat hanya waktu baca, bukan posisi: memundurkan posisi ke
     * halaman 1 setiap kali buku dibuka akan membuat fitur "lanjut membaca"
     * tidak pernah berguna.
     */
    public function read(Request $request, Book $book): View|RedirectResponse
    {
        if (! $book->hasFile()) {
            return $this->warning('books.show', 'File digital buku ini belum tersedia.', ['book' => $book]);
        }

        $user = $request->user();

        if (! $book->canBeReadBy($user)) {
            abort(403, 'Anda tidak punya izin membaca buku ini.');
        }

        /*
         * `last_page` harus di-cast ke int secara eksplisit. Kolomnya punya
         * default 0 di database, tapi saat baris baru dibuat Eloquent tidak
         * menyertakan kolom yang tidak ada di array insert, jadi atributnya
         * `null` di objek walau database sudah berisi 0. Tanpa cast ini,
         * pembacaan pertama kali akan jadi HTTP 500 (TypeError).
         */
        $existing = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        $savedPage = (int) ($existing?->last_page ?? 0);

        // `?page=` dari "lanjut membaca" menang atas posisi tersimpan, karena
        // user yang menekan tautan itu memang sedang mencari halaman tertentu.
        $requested = $request->integer('page');
        $page = $this->recordReading->clampPage($requested > 0 ? $requested : $savedPage, $book);

        // Dicatat setelah halaman dihitung, supaya kunjungan pertama kali
        // mengingat halaman yang benar-benar dibuka.
        //
        // Hanya untuk anggota: staff memakai reader ini untuk pratinjau buku,
        // dan tidak ada halaman "riwayat baca" yang menampilkan data mereka,
        // jadi barisnya cuma jadi sampah data.
        if ($user->isAnggota()) {
            $this->recordReading->visit($user, $book, $page);
        }

        return view('books.read', [
            'book' => $book,
            'page' => $page,
            'totalPages' => (int) $book->pages > 0 ? (int) $book->pages : null,
            'savedPage' => $savedPage,
        ]);
    }

    /**
     * Stream file PDF ke user yang berhak.
     *
     * Sengaja memakai `Content-Disposition: inline` (bukan `attachment`) supaya
     * PDF tampil di tab browser sesuai keputusan, dan file tetap di-stream
     * dari disk privat — tidak pernah ada URL publik yang bisa ditebak.
     */
    public function file(Request $request, Book $book): StreamedResponse
    {
        if (! $book->canBeReadBy($request->user())) {
            abort(403, 'Anda tidak punya izin membaca buku ini.');
        }

        $disk = Storage::disk($book::bookFileDisk());

        if (! $disk->exists($book->file)) {
            abort(404, 'File digital buku ini tidak ditemukan.');
        }

        return $disk->response(
            $book->file,
            $book->title.'.pdf',
            // inline = tampil di browser, attachment = diunduh.
            ['Content-Disposition' => 'inline; filename="'.addslashes($book->title).'.pdf"'],
        );
    }

    public function create(): View
    {
        return view('books.create', [
            'book' => new Book,
            ...$this->formOptions(),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $book = Book::create($this->bookPayload($request));

        return $this->success('books.show', 'Buku berhasil ditambahkan.', ['book' => $book]);
    }

    public function edit(Book $book): View
    {
        return view('books.edit', [
            'book' => $book,
            ...$this->formOptions(),
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $book->update($this->bookPayload($request, $book));

        return $this->success('books.show', 'Buku berhasil diperbarui.', ['book' => $book]);
    }

    /**
     * Opsi dropdown untuk form buku, dipakai `create()`, `edit()`, dan filter
     * `index()`.
     *
     * Ketiganya butuh daftar yang persis sama dan urutan yang persis sama,
     * supaya pilihan di form edit sama dengan yang tampil di form create. Kalau
     * salah satu ditulis ulang sendiri, cepat atau lambat urutannya berbeda
     * dan pilihan di dropdown terlihat tidak konsisten antar halaman.
     *
     * @return array{categories: Collection<int, Category>, authors: Collection<int, Author>, publishers: Collection<int, Publisher>}
     */
    protected function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
        ];
    }

    /**
     * Selesaikan payload buku dari request yang sudah tervalidasi.
     *
     * Field upload dipisah karena tidak boleh ikut `validated()` apa adanya:
     * `cover` dan `file` dipegang trait `HandlesUploads` (nama file di-generate
     * ulang, file lama dihapus saat diganti), sedangkan `remove_cover` dan
     * `remove_file` adalah flag UI tanpa kolom sendiri.
     *
     * @return array<string, mixed>
     */
    protected function bookPayload(BookRequest $request, ?Book $book = null): array
    {
        return $request->safe()->except(['cover', 'file', 'remove_cover', 'remove_file'])
            + $this->bookFilePayload($request, $book);
    }

    public function destroy(Book $book): RedirectResponse
    {
        // Hapus berkas fisik dulu supaya tidak ada file yatim di storage
        // walau proses hapus baris database gagal di tengah jalan.
        $this->deleteUpload($book->cover, $book::coverDisk());
        $this->deleteUpload($book->file, $book::bookFileDisk());

        $book->delete();

        return $this->success('books.index', 'Buku berhasil dihapus.');
    }

    /**
     * Terima hanya nama kolom & arah yang diizinkan, agar request tidak bisa
     * menyuntikkan kolom acak ke orderBy.
     *
     * @return array{column: string, direction: string, key: string}
     */
    protected function resolveSort(?string $key): array
    {
        return match ($key) {
            'title' => ['column' => 'title', 'direction' => 'asc', 'key' => 'title'],
            'oldest' => ['column' => 'publication_year', 'direction' => 'asc', 'key' => 'oldest'],
            'latest' => ['column' => 'created_at', 'direction' => 'desc', 'key' => 'latest'],
            default => ['column' => 'created_at', 'direction' => 'desc', 'key' => 'latest'],
        };
    }
}
