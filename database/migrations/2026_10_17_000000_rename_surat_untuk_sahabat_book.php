<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ganti judul "Surat untuk Sahabat" -> "Surat untuk Masa Depan"
     * (permintaan pengguna: tanpa kata Sahabat/Teman).
     *
     * Kenapa migration, bukan seeder ulang: BookSeeder memakai
     * updateOrCreate per ISBN, jadi judul baru akan ikut saat seed
     * berikutnya — tapi data yang SUDAH ada (tabel books + payload
     * JSON di tabel notifications yang menyimpan judul saat notifikasi
     * dibuat) tidak berubah sendiri. Migration ini menyapu keduanya
     * supaya lonceng + halaman notifikasi lama ikut bersih.
     */
    public function up(): void
    {
        $old = 'Surat untuk Sahabat';
        $new = 'Surat untuk Masa Depan';

        DB::table('books')
            ->where('title', $old)
            ->update([
                'title' => $new,
                'description' => $new.' merupakan salah satu koleksi digital Perpustakaan Digital.',
            ]);

        // Payload notifikasi menyimpan judul sebagai teks (subject/book_title),
        // bukan relasi live — jadi sapu JSON-nya juga.
        // Pakai chunk() biasa (bukan chunkById) karena PK notifications
        // adalah UUID string, bukan auto-increment.
        DB::table('notifications')
            ->where('data', 'like', '%'.$old.'%')
            ->orderBy('created_at')
            ->chunk(200, function ($rows) use ($old, $new) {
                foreach ($rows as $row) {
                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => str_replace($old, $new, $row->data)]);
                }
            });
    }

    public function down(): void
    {
        $old = 'Surat untuk Masa Depan';
        $new = 'Surat untuk Sahabat';

        DB::table('books')
            ->where('title', $old)
            ->update([
                'title' => $new,
                'description' => $new.' merupakan salah satu koleksi digital Perpustakaan Digital.',
            ]);

        DB::table('notifications')
            ->where('data', 'like', '%'.$old.'%')
            ->orderBy('created_at')
            ->chunk(200, function ($rows) use ($old, $new) {
                foreach ($rows as $row) {
                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => str_replace($old, $new, $row->data)]);
                }
            });
    }
};
