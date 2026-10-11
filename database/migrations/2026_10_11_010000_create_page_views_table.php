<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel penghitung kunjungan halaman publik.
 *
 * Satu baris per rute (bukan per request): kolom `view_count` bertambah +1
 * setiap kali halaman itu berhasil dibuka. Ini jauh lebih hemat daripada
 * menyimpan satu baris per kunjungan — untuk 10.000 kunjungan katalog,
 * tabel ini tetap hanya 4 baris.
 *
 * `route_name` dipakai, bukan URL mentah: /buku/1 dan /buku/2 adalah dua
 * halaman berbeda tapi satu fitur yang sama ("detail buku"), dan yang
 * ingin dilihat pustakawan adalah berapa kali fitur itu dibuka — bukan
 * berapa kali tiap buku tertentu dilihat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 100)->unique();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
