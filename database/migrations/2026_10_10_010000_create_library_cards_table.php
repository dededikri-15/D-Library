<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            /*
             * Nomor kartu, format DLP-000001 (berbasis ID user).
             *
             * Deterministik: satu user selalu menghasilkan nomor yang sama,
             * jadi tidak ada risiko tabrakan nomor saat pembuatan bersamaan.
             * Unique constraint tetap dipasang sebagai jaring pengaman —
             * kalau ada kesalahan pengkodean di masa depan, database yang
             * menolak, bukan data korup yang baru ketahuan belakangan.
             */
            $table->string('card_number')->unique();

            /*
             * Masa berlaku kartu.
             *
             * Kartu yang sudah lewat tanggal ini dianggap tidak berlaku.
             * Tidak perlu kolom `status` terpisah karena status bisa
             * diturunkan dari tanggal ini — satu sumber kebenaran.
             */
            $table->date('valid_until');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_cards');
    }
};
