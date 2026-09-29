<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kontak penerbit.
     *
     * Kolom ini menutup celah yang sudah ada di form dan sudah divalidasi
     * `PublisherRequest`, tapi belum pernah ada di tabel: input Pustakawan
     * divalidasi lalu dibuang diam-diam oleh proteksi mass-assignment, jadi
     * flash "berhasil" tetap muncul padahal email dan telepon hilang.
     *
     * Nullable karena penerbit lama tidak punya data ini, dan form memang
     * menandai keduanya opsional. Tidak ada index: keduanya hanya dibaca
     * sebagai data tampilan, bukan bagian dari pencarian atau filter.
     */
    public function up(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('website');
            $table->string('phone', 30)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
        });
    }
};
