<?php

namespace App\Providers;

use App\Mail\Transport\DatabaseTransport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('database', fn () => new DatabaseTransport);

        $security = (array) config('perpustakaan.security');

        /*
         * Named limiter, dipakai lewat `->middleware('throttle:login')` di
         * routes/web.php. Batas per email + IP sendiri tetap dihitung di
         * LoginRequest memakai config yang sama, supaya tidak ada angka yang
         * ditulis dua kali.
         */
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) ($security['login_per_minute'] ?? 10))
                ->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute((int) ($security['register_per_minute'] ?? 6))
                ->by($request->ip());
        });

        // Membatasi pengambilan PDF agar satu akun tidak bisa mengunduh
        // seluruh koleksi dengan cepat.
        RateLimiter::for('file-read', function (Request $request) {
            return Limit::perMinute((int) ($security['file_read_per_minute'] ?? 30))
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Endpoint pencarian hidup (Task 14.6) dipanggil sekali per ketikan
         * setelah debounce, jadi batasnya jauh lebih longgar daripada login.
         * Yang dicegah bukan request-nya, tapi mengetik tanpa jeda: pola
         * "ketik 2 huruf, tunggu, ketik 2 huruf lagi" yang membuat satu orang
         * menembak puluhan query `like` per menit dari satu browser.
         */
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute((int) ($security['search_per_minute'] ?? 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Batasi setiap parameter id supaya hanya angka yang lolos ke Route Model
         * Binding.
         *
         * Tanpa ini, /buku/abc akan diteruskan ke query `where id = 'abc'`.
         * Di SQLite itu aman (menghasilkan null -> 404), TAPI di PostgreSQL
         * kolom bigint menolak teks dan melempar QueryException -> HTTP 500.
         * Jadi test yang jalan di SQLite tidak akan pernah menangkap masalah ini;
         * batasannya harus di level routing.
         */
        foreach (['book', 'category', 'author', 'publisher', 'loan', 'user', 'readingHistory'] as $parameter) {
            Route::pattern($parameter, '[0-9]+');
        }
    }
}
