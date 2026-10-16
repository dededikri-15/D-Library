<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pengganti sistem denda uang (Rp 1.000/hari) dengan sanksi larangan
 * meminjam selama 72 jam.
 *
 * - `users.suspended_until`: batas akhir sanksi. Sanksi dianggap BERLAKU
 *   selama timestamp ini masih di masa depan, dan otomatis berakhir sendiri
 *   setelah lewat tanpa perlu scheduler — satu kolom, nol pekerjaan latar
 *   belakang. Diisi saat pustakawan memproses pengembalian buku yang
 *   terlambat (lihat LoanController::completeReturn).
 * - `loans.fine` dan `loans.fine_paid_at` dihapus: uang bukan bagian dari
 *   alur peminjaman lagi. Kolom lama tidak dipertahankan "jaga-jaga" karena
 *   hanya menimbulkan dua sumber kebenaran — tampilan bisa bilang "lunas"
 *   padahal aturannya sudah berbeda.
 */

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null = tidak ada sanksi. Non-null di masa depan = sanksi
            // sedang berjalan; non-null di masa lampau = sanksi sudah lewat.
            $table->timestamp('suspended_until')->nullable()->after('avatar');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['fine', 'fine_paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedInteger('fine')->default(0)->after('renew_count');
            $table->timestamp('fine_paid_at')->nullable()->after('fine');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('suspended_until');
        });
    }
};
