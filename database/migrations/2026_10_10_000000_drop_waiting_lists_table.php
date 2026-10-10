<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur daftar tunggu (waiting list) dihapus atas permintaan pengguna
     * (Task 24.x -> penghapusan).
     *
     * Tabel `waiting_lists` dibuang lewat migration, bukan dihapus manual
     * dari database, supaya perubahan schema tetap tercatat dan bisa
     * dijalankan ulang di environment lain.
     */
    public function up(): void
    {
        Schema::dropIfExists('waiting_lists');

        /*
         * Notifikasi lama bertipe `book_available` / `waiting_list_joined` /
         * `waiting_list_left` ikut dibersihkan. View notifikasi memakai
         * fallback `Lang::has`, jadi baris yang tersisa tidak membuat error —
         * tapi tampilannya jadi judul generik + body kosong, yang hanya
         * membingungkan. Dihapus sekalian supaya lonceng bersih.
         */
        $types = ['book_available', 'waiting_list_joined', 'waiting_list_left'];

        DB::table('notifications')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($types) {
                foreach ($rows as $row) {
                    $data = json_decode($row->data, true);

                    if (is_array($data) && in_array($data['type'] ?? '', $types, true)) {
                        DB::table('notifications')->where('id', $row->id)->delete();
                    }
                }
            });
    }

    public function down(): void
    {
        // Perubahan ini bersifat destruktif; tidak dikembalikan.
    }
};
