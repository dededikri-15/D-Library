<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             * Tanggal lahir pengguna.
             *
             * Nullable karena user lama (yang terdaftar sebelum fitur ini
             * ada) belum punya data. User lama wajib mengisi tanggal lahir
             * lewat halaman edit profil sebelum bisa melihat kartu
             * perpustakaan. Dipakai sebagai dasar nomor kartu (DDMMYY).
             */
            $table->date('date_of_birth')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};
