<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            /*
             * Penanda "peringatan keterlambatan dalam aplikasi sudah dikirim".
             *
             * Terpisah dari `overdue_notified_at` karena peristawanya memang
             * berbeda waktu dan penerima:
             *
             * - `overdue_alerted_at` (kolom ini): notifikasi dalam aplikasi
             *   (lonceng) untuk anggota SEMUA pustakawan, dikirim begitu
             *   status `overdue` terdeteksi — bisa lewat halaman yang dibuka
             *   user maupun scheduler per jam.
             * - `overdue_notified_at`: email harian anggota, tetap hanya dari
             *   command `loans:remind` jam 08:00.
             *
             * Dua kolom supaya klaimnya tidak berebut: kalau satu kolom dipakai
             * bersama, deteksi pertama (mis. tamu membuka beranda) akan
             * "memakan" klaim email dan anggota tidak pernah menerima email
             * pengingatnya.
             */
            $table->timestamp('overdue_alerted_at')->nullable()->after('overdue_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('overdue_alerted_at');
        });
    }
};
