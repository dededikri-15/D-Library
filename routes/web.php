<?php

use App\Http\Controllers\AnggotaDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\ReadingHistoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // throttle:register mencegah pembuatan akun massal lewat form publik.
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // Pengaman luar per IP. Batas per email+IP ada di dalam LoginRequest.
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Modul staff: pustakawan
|--------------------------------------------------------------------------
|
| PENTING: route modul staff (terutama `buku/create`) harus didaftarkan
| SEBELUM route katalog publik `/buku/{book}`. Kalau dibalik, request ke
| /buku/create akan tertangkap route publik itu dan dianggap mencari buku
| dengan id "create", sehingga hasilnya 404.
|
*/

Route::middleware(['auth', 'role:pustakawan'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master data (PRD §6)
    Route::resource('kategori', CategoryController::class)
        ->parameters(['kategori' => 'category'])
        ->names('categories')
        ->except('show')
        ->whereNumber('category');

    Route::resource('penulis', AuthorController::class)
        ->parameters(['penulis' => 'author'])
        ->names('authors')
        ->except('show')
        ->whereNumber('author');

    Route::resource('penerbit', PublisherController::class)
        ->parameters(['penerbit' => 'publisher'])
        ->names('publishers')
        ->except('show')
        ->whereNumber('publisher');

    Route::post('kategori/quick', [CategoryController::class, 'storeQuick'])->name('categories.quick');
    Route::post('penulis/quick', [AuthorController::class, 'storeQuick'])->name('authors.quick');
    Route::post('penerbit/quick', [PublisherController::class, 'storeQuick'])->name('publishers.quick');

    // Manajemen buku (PRD §6)
    Route::resource('buku', BookController::class)
        ->parameters(['buku' => 'book'])
        ->names('books')
        ->except(['index', 'show'])
        ->whereNumber('book');

    Route::resource('pengguna', UserController::class)
        ->parameters(['pengguna' => 'user'])
        ->names('users')
        ->except('show')
        ->whereNumber('user');

    // Peminjaman (PRD §9)
    Route::get('/peminjaman', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/peminjaman', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/peminjaman/{loan}/kembalikan', [LoanController::class, 'returnBook'])
        ->whereNumber('loan')
        ->name('loans.return');
    Route::delete('/peminjaman/{loan}', [LoanController::class, 'destroy'])
        ->whereNumber('loan')
        ->name('loans.destroy');
});

/*
|--------------------------------------------------------------------------
| Katalog & detail buku (publik, PRD §4 & §5)
|--------------------------------------------------------------------------
|
| URL memakai Bahasa Indonesia, tapi NAMA ROUTE dan NAMA PARAMETER tetap
| Bahasa Inggris. Ini wajib: Route Model Binding implicit mencocokkan nama
| parameter route dengan nama parameter di method controller
| (mis. `Book $book` butuh `{book}`, bukan `{buku}`).
|
| Group ini diletakkan setelah modul staff — lihat catatan di atas.
|
*/

Route::get('/buku', [BookController::class, 'index'])->name('books.index');

/*
 * Pencarian hidup untuk pratinjau hasil (Task 14.6).
 *
 * Ditaruh SEBELUM `/buku/{book}` dengan alasan yang sama seperti blok staff di
 * atas: kalau `/buku/{book}` didaftarkan lebih dulu, RouteCollection akan
 * menyimpan satu nama route per kombinasi method+URI dan `books.search` bisa
 * tertimpa. Pola `/{book}` memang sudah dibatasi `whereNumber` sehingga
 * "pencarian" tidak akan pernah tertangkap sebagai binding, tapi urutan
 * pendaftaran tetap dijaga agar tidak bergantung pada detail itu.
 *
     * Batas throttle-nya longgar (lihat `search` di AppServiceProvider) karena
     * dipanggil per ketikan, bukan per halaman. Respons 429 harus ditangani JS
     * sebagai "pencarian sedang dinonaktifkan sementara", bukan "terjadi
     * kesalahan" — inilah yang akan dilihat user kalau kuota habis.
 */
Route::get('/buku/pencarian', [BookController::class, 'search'])
    ->middleware('throttle:search')
    ->name('books.search');

Route::get('/buku/{book}', [BookController::class, 'show'])->whereNumber('book')->name('books.show');

/*
|--------------------------------------------------------------------------
| Halaman kategori publik (Task 8.7)
|--------------------------------------------------------------------------
|
| CRUD kategori milik staff sudah memakai URI /kategori (lihat modul staff di
| atas), jadi halaman publik ini harus di URI lain. Kalau dua route berbagi
| URI, RouteCollection hanya menyimpan satu nama route per kombinasi
| method+URI — nama `categories.index` milik staff tertimpa dan redirect
| setelah simpan jadi RouteNotFoundException.
|
|>/kategori/{category} juga tidak dibuat. Link ke katalog memakai query string
| (?category=slug) supaya hanya ada satu cara menampilkan buku per kategori.
|
*/

Route::get('/kategori-buku', [CategoryController::class, 'publicIndex'])->name('categories.public');

/*
|--------------------------------------------------------------------------
| Pembaca digital (PRD §12)
|--------------------------------------------------------------------------
|
| Kedua route wajib login. Pemeriksaan hak akses yang sesungguhnya ada di
| controller (BookController::canBeReadBy): staff, atau anggota yang sedang
| meminjam buku dengan status `borrowed`. Jadi tidak ada URL PDF publik
| yang bisa ditebak atau dibagikan.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/buku/{book}/baca', [BookController::class, 'read'])->whereNumber('book')->name('books.read');
    Route::get('/buku/{book}/berkas', [BookController::class, 'file'])
        ->whereNumber('book')
        ->middleware('throttle:file-read')
        ->name('books.file');
});

/*
|--------------------------------------------------------------------------
| Modul anggota
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:anggota'])->group(function () {
    Route::get('/anggota', [AnggotaDashboardController::class, 'index'])->name('anggota.dashboard');

    // Peminjaman mandiri (PRD §9, Task 10.1 & 10.11).
    //
    // Route `books.borrow` memakai POST, jadi tidak akan pernah tertangkap
    // route publik GET /buku/{book} meski posisinya sesudah group katalog.
    // Otorisasi sebenarnya ada di BookPolicy::borrow; group `role:anggota`
    // di sini hanya lapisan pertama yang lebih murah.
    Route::post('/buku/{book}/pinjam', [LoanController::class, 'borrow'])
        ->whereNumber('book')
        ->name('books.borrow');
    Route::get('/riwayat-peminjaman', [LoanController::class, 'mine'])->name('loans.mine');
    Route::post('/riwayat-peminjaman/{loan}/ajukan-pengembalian', [LoanController::class, 'requestReturn'])
        ->whereNumber('loan')
        ->name('loans.mine.request-return');

    Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorit/{book}', [FavoriteController::class, 'store'])->whereNumber('book')->name('favorites.store');
    Route::delete('/favorit/{book}', [FavoriteController::class, 'destroy'])->whereNumber('book')->name('favorites.destroy');

    Route::get('/riwayat-baca', [ReadingHistoryController::class, 'index'])->name('reading-histories.index');
    Route::post('/riwayat-baca', [ReadingHistoryController::class, 'store'])->name('reading-histories.store');
    Route::delete('/riwayat-baca/{readingHistory}', [ReadingHistoryController::class, 'destroy'])
        ->whereNumber('readingHistory')
        ->name('reading-histories.destroy');
});

Route::middleware(['auth', 'role:anggota'])->group(function () {
    Route::get('/mailbox', [MailboxController::class, 'index'])->name('mailbox.index');
    Route::get('/mailbox/{id}', [MailboxController::class, 'show'])->name('mailbox.show');
    Route::delete('/mailbox/{id}', [MailboxController::class, 'destroy'])->name('mailbox.destroy');
});
