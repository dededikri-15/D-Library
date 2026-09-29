<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $authors = [
            ['name' => 'Budi Santoso', 'biography' => 'Penulis fiksi dan esai tentang kehidupan sehari-hari.'],
            ['name' => 'Siti Aminah', 'biography' => 'Penulis panduan pendidikan dan literasi anak.'],
            ['name' => 'Andi Prasetyo', 'biography' => 'Praktisi teknologi yang menulis tentang pemrograman.'],
            ['name' => 'Dewi Lestari', 'biography' => 'Penulis sejarah lokal dan biografi tokoh Indonesia.'],
            ['name' => 'Hendra Wijaya', 'biography' => 'Penulis buku keagamaan tentang nilai dan akhlak.'],
            ['name' => 'Rina Kusuma', 'biography' => 'Puisi dan novel bertema keluarga.'],
        ];

        foreach ($authors as $author) {
            Author::updateOrCreate(['name' => $author['name']], $author);
        }
    }
}
