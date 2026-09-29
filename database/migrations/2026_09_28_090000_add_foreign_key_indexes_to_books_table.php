<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk kolom foreign key di tabel `books`.
 *
 * Kenapa migration ini terpisah dan baru, bukan disisipkan ke migration
 * `create_books_table`:
 *
 * 1. `foreignId()->constrained()` di Laravel TIDAK membuat index. Dia hanya
 *    membuat kolom bigint dan constraint FK. Di MySQL, index untuk FK dibuat
 *    otomatis oleh mesin database. PostgreSQL tidak — sehingga kode yang
 *    terlihat benar tetap menghasilkan tabel tanpa index di sisi PostgreSQL.
 * 2. Migration `create_books_table` sudah pernah dijalankan di database dev,
 *    jadi menyisipkan baris ke sana tidak akan mengubah apa pun. Yang
 *    dibutuhkan adalah migration baru yang benar-benar dijalankan.
 *
 * Tanpa index di sini, filter kategori / penulis / penerbit pada katalog
 * (Task 9.4 dan 9.5) selalu melakukan sequential scan terhadap seluruh tabel
 * `books`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('author_id');
            $table->index('publisher_id');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['author_id']);
            $table->dropIndex(['publisher_id']);
        });
    }
};
