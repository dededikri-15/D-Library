<?php

use App\Models\Book;
use App\Support\InventoryCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menulis ulang SELURUH kode eksemplar yang ada memakai format DDC.
 *
 * Sebelum migration ini kode eksemplar campur-baur: sebagian sisa format
 * lama `BK-%06d-%03d` dari migration pembuat tabel, sebagian lagi `EX-`
 * acak dari hook BookCopy::creating. Keduanya tidak memberi petunjuk rak,
 * sehingga pustakawan tetap harus membuka detail buku hanya untuk tahu
 * menaruh eksemplar di mana.
 *
 * Setelah dijalankan, semua kode berbentuk `{DDC}/{penulis}/{urut}` —
 * contoh `959.8/WIJ/01` — sehingga nomor inventaris sekaligus menjadi
 * petunjuk klasifikasi.
 *
 * Penomoran dihitung lewat counter per prefix yang berjalan lintas buku,
 * bukan per buku: dua buku dengan DDC dan penulis sama akan melanjutkan
 * urutan yang sama (`.../01`, `.../02`, `.../03`) dan tidak pernah
 * berebut kode. Kode baru formatnya berbeda dari kode lama (ada `/`),
 * jadi tidak mungkin bertabrak dengan baris yang belum tersentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        $counters = [];

        foreach (Book::query()->with(['author', 'category'])->orderBy('id')->get() as $book) {
            $prefix = InventoryCode::prefixFor($book);

            foreach ($book->copies()->orderBy('id')->pluck('id') as $copyId) {
                $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;

                DB::table('book_copies')
                    ->where('id', $copyId)
                    ->update(['inventory_code' => $prefix.sprintf('%02d', $counters[$prefix])]);
            }
        }
    }

    public function down(): void
    {
        // Regenerasi kode tidak bisa dibalik: kode lama sudah hilang dan
        // tidak tersimpan di mana pun. Schema tidak berubah, jadi down()
        // cukup kosong — migration dianggap aman dijalankan ulang.
    }
};
