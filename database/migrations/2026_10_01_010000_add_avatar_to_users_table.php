<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto profil opsional untuk user.
     *
     * Nullable karena foto profil tidak wajib (PRD §3 "Mengelola profil"): user
     * tanpa foto tetap valid, tampilannya memakai avatar huruf (inisial nama).
     *
     * Disimpan sebagai path relatif, bukan URL — URL-nya bisa berubah kalau
     * `APP_URL` atau konfigurasi disk berubah, sedangkan path-nya tetap menunjuk
     * berkas yang sama. Ini pola yang sama dengan `authors.photo` dan
     * `books.cover`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
