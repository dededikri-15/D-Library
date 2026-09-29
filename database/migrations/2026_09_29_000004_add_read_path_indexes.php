<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk tiga jalur baca yang paling sering dipakai.
     *
     * Setiap index di sini hasil `EXPLAIN ANALYZE` di PostgreSQL 17, bukan
     * tebakan. Angka di komentar adalah hasil ukur, jadi kalau nanti bisa
     * dibandingkan ulang.
     *
     * Yang TIDAK diberi index, dan alasannya:
     * - `books.title` sudah ada (`books_title_index`) dan dipakai untuk sort judul.
     * - `books.status`, `category_id`, `author_id`, `publisher_id` sudah ter-index
     *   dari tabel asalnya.
     * - Pencarian katalog pakai `LIKE '%...%'`. Pola di depan wildcard tidak
     *   bisa memakai index btree sama sekali, jadi `books.title` yang sudah
     *   ter-index pun tidak menolongnya. Butuh full-text search (tsvector/GIN)
     *   untuk pencarian, dan itu di luar cakupan proyek ini. Sudah dicatat di
     *   `Controller::applyIsbnSearch()`.
     */
    public function up(): void
    {
        // Sort bawaan katalog: `ORDER BY created_at DESC`. Tanpa index ini
        // PostgreSQL scan seluruh tabel lalu sort di memori tiap request:
        // 3.307 ms -> 0.092 ms pada 4.000 buku (36x).
        Schema::table('books', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Sort "terlama" katalog: `ORDER BY publication_year ASC`.
        // Sama seperti di atas: 2.818 ms -> 0.093 ms (30x).
        Schema::table('books', function (Blueprint $table) {
            $table->index('publication_year');
        });

        // Command `loans:mark-overdue` yang dijadwalkan tiap jam (lihat
        // `routes/console.php`) memakai `WHERE status = ? AND due_at < ?`. Index
        // yang sudah ada di `loans` punya kolom depan `user_id` dan `book_id`, jadi
        // tidak bisa dipakai untuk filter `status` saja - hasilnya Seq Scan tiap jam:
        // 378 ms -> 34.5 ms pada 30.000 baris (11x), dan plan-nya berubah dari
        // Seq Scan ke Bitmap Index Scan.
        //
        // Catatan: query yang sama juga dipanggil langsung oleh DashboardController,
        // AnggotaDashboardController, dan LoanController - bukan cuma lewat scheduler.
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_at']);
        });

        // Penting: `dropIndex` menerima array kolom, bukan string. Kalau
        // ditulis `dropIndex('created_at')` Laravel memperlakukannya sebagai
        // NAMA index dan akan menjalankan `DROP INDEX "created_at"`, yang
        // gagal karena nama aslinya `books_created_at_index`.
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['publication_year']);
            $table->dropIndex(['created_at']);
        });
    }
};
