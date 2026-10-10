<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update nomor kartu yang sudah ada ke format baru (DDMMYY).
     *
     * Format lama (TAHUN-URUTAN, contoh: 2026-0008) diganti dengan DDMMYY
     * dari tanggal lahir user masing-masing. Hanya user yang SUDAH punya
     * tanggal lahir yang di-update — user tanpa tanggal lahir mempertahankan
     * nomor lama sampai mereka mengisi tanggal lahir di edit profil
     * (saat itu nomor otomatis di-update oleh ProfileController).
     */
    public function up(): void
    {
        $cards = DB::table('library_cards')
            ->join('users', 'users.id', '=', 'library_cards.user_id')
            ->whereNotNull('users.date_of_birth')
            ->select('library_cards.id', 'users.date_of_birth')
            ->get();

        foreach ($cards as $card) {
            $dob = Carbon::parse($card->date_of_birth);
            DB::table('library_cards')
                ->where('id', $card->id)
                ->update(['card_number' => $dob->format('dmy')]);
        }
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan ke format lama karena nomor lama
        // (berbasis ID user) sudah tidak disimpan di mana pun.
    }
};
