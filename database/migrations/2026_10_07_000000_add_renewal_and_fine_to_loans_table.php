<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Berapa kali peminjaman ini sudah diperpanjang; dibatasi
            // config('perpustakaan.loan.max_renewals').
            $table->unsignedTinyInteger('renew_count')->default(0)->after('status');

            // Denda di-snapshot saat buku dikembalikan, supaya nilai yang
            // pernah ditagih tidak ikut berubah walau tarif per hari
            // berubah kemudian. Selama masih terlambat, tampilan memakai
            // perhitungan hidup (Loan::liveFine()), bukan kolom ini.
            $table->unsignedInteger('fine')->default(0)->after('renew_count');

            // Dicatat pustakawan setelah denda dibayar (tunai/transfer di
            // meja sirkulasi). Null berarti "belum lunas".
            $table->timestamp('fine_paid_at')->nullable()->after('fine');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['renew_count', 'fine', 'fine_paid_at']);
        });
    }
};
