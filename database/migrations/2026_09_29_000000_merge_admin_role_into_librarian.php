<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'pustakawan']);
    }

    public function down(): void
    {
        // This data migration is intentionally irreversible: converted users
        // cannot be distinguished from users who were already librarians.
    }
};
