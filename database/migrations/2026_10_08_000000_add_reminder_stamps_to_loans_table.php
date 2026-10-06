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
             * Dua penanda "sudah dikirim" — kenapa bukan tabel notifikasi:
             * notifikasi di sini lahir dari jadwal (H-1 dan lewat tempo),
             * bukan dari aksi user, dan hanya boleh terkirim sekali per
             * peminjaman. Kalau pakai tabel terpisah, "sudah pernah kirim"
             * jadi query JOIN; dengan kolom di baris yang sama, jadwal
             * cukup UPDATE ... WHERE kolom IS NULL — atomik dan tanpa dobel
             * walau scheduler jalan dua kali bersamaan.
             */
            $table->timestamp('due_reminder_sent_at')->nullable()->after('fine_paid_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('due_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['due_reminder_sent_at', 'overdue_notified_at']);
        });
    }
};
