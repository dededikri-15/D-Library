<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_cards', function (Blueprint $table) {
            // Stempel waktu anggota MENGAJUKAN perpanjangan ke pustakawan.
            // Null = belum ada pengajuan (atau sudah diproses — pustakawan
            // yang menekan tombol perpanjang mengosongkan kolom ini lagi).
            //
            // Tanpa kolom ini, "sudah pernah mengajukan atau belum" harus
            // ditebak dari lonceng pustakawan yang belum dibaca — rapuh,
            // dan anggota tidak bisa tahu status pengajuannya sendiri.
            $table->timestamp('renewal_requested_at')->nullable()->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('library_cards', function (Blueprint $table) {
            $table->dropColumn('renewal_requested_at');
        });
    }
};
