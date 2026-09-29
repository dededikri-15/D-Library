<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('name', 'Admin Perpustakaan')
            ->where('email', 'admin@perpustakaan.test')
            ->delete();
    }

    public function down(): void
    {
        // This demo account is intentionally not recreated on rollback.
    }
};
