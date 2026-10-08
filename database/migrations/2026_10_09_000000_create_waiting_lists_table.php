<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();

            /*
             * Penanda "notifikasi 'buku sudah tersedia' sudah dikirim".
             *
             * Null = anggota masih menunggu dan BELUM pernah dikabari.
             * Terisi = anggota sudah dikabari pada ronde ketersediaan ini.
             *
             * Kolom ini dipakai dua hal:
             *
             * 1. Klaim atomik (`UPDATE ... WHERE notified_at IS NULL`) supaya
             *    dua jalur pengembalian yang berjalan bersamaan tidak
             *    mengirim notifikasi dobel — pola yang sama dengan
             *    `overdue_alerted_at` di tabel loans.
             * 2. Jendela kedaluwarsa: entri yang sudah dikabari tapi tidak
             *    ditindaklanjuti dihapus oleh command `waiting-lists:expire`
             *    setelah `waiting_list.notify_window_hours` jam.
             *
             * Di-reset ke null saat buku kembali dipinjam habis, supaya
             * ronde pengembalian berikutnya mengabari anggota yang masih
             * mengantre (lihat BorrowBook).
             */
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            // Satu baris per anggota per buku: klik ganda tidak boleh
            // membuat antrean dobel.
            $table->unique(['user_id', 'book_id']);

            // Query notifikasi menyaring per buku; index prefix unique di
            // atas (user_id, book_id) tidak bisa dipakai untuk itu.
            $table->index('book_id');

            // Pembersihan kedaluwarsa memindai kolom penanda ini.
            $table->index('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
