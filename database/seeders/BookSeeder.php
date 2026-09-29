<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'slug');
        $authorIds = Author::pluck('id', 'name');
        $publisherIds = Publisher::pluck('id', 'name');

        $books = [
            ['isbn' => '978-602-001-001-1', 'title' => 'Cerita Pendek Kota Kecil', 'category' => 'fiksi', 'author' => 'Budi Santoso', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2019, 'pages' => 184],
            ['isbn' => '978-602-001-002-8', 'title' => 'Lembutnya Senja', 'category' => 'fiksi', 'author' => 'Budi Santoso', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2021, 'pages' => 236],
            ['isbn' => '978-602-001-003-5', 'title' => 'Panduan Literasi Anak Usia Dini', 'category' => 'pendidikan', 'author' => 'Siti Aminah', 'publisher' => 'Penerbit Ilmiah Nusantara', 'year' => 2020, 'pages' => 312],
            ['isbn' => '978-602-001-004-2', 'title' => 'Metode Belajar Aktif di Sekolah', 'category' => 'pendidikan', 'author' => 'Siti Aminah', 'publisher' => 'Penerbit Ilmiah Nusantara', 'year' => 2022, 'pages' => 268],
            ['isbn' => '978-602-001-005-9', 'title' => 'Dasar-Dasar Pemrograman PHP', 'category' => 'teknologi', 'author' => 'Andi Prasetyo', 'publisher' => 'Penerbit Teknologi Digital', 'year' => 2021, 'pages' => 424],
            ['isbn' => '978-602-001-006-6', 'title' => 'Membangun Aplikasi dengan Laravel', 'category' => 'teknologi', 'author' => 'Andi Prasetyo', 'publisher' => 'Penerbit Teknologi Digital', 'year' => 2023, 'pages' => 486],
            ['isbn' => '978-602-001-007-3', 'title' => 'Pengantar Basis Data Relasional', 'category' => 'teknologi', 'author' => 'Andi Prasetyo', 'publisher' => 'Penerbit Teknologi Digital', 'year' => 2020, 'pages' => 352],
            ['isbn' => '978-602-001-008-0', 'title' => 'Sejarah Kota Nusantara', 'category' => 'sejarah', 'author' => 'Dewi Lestari', 'publisher' => 'Penerbit Sejarah Nusantara', 'year' => 2018, 'pages' => 298],
            ['isbn' => '978-602-001-009-7', 'title' => 'Pahlawan yang Terlupakan', 'category' => 'sejarah', 'author' => 'Dewi Lestari', 'publisher' => 'Penerbit Sejarah Nusantara', 'year' => 2022, 'pages' => 264],
            ['isbn' => '978-602-001-010-3', 'title' => 'Etika dan Akhlak', 'category' => 'agama', 'author' => 'Hendra Wijaya', 'publisher' => 'Penerbit Cendana', 'year' => 2019, 'pages' => 220],
            ['isbn' => '978-602-001-011-0', 'title' => 'Memahami Makna Ibadah', 'category' => 'agama', 'author' => 'Hendra Wijaya', 'publisher' => 'Penerbit Cendana', 'year' => 2021, 'pages' => 246],
            ['isbn' => '978-602-001-012-7', 'title' => 'Puisi untuk Langit', 'category' => 'sastra', 'author' => 'Rina Kusuma', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2020, 'pages' => 132],
            ['isbn' => '978-602-001-013-4', 'title' => 'Rumah Tanpa Pintu', 'category' => 'sastra', 'author' => 'Rina Kusuma', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2023, 'pages' => 278],
            ['isbn' => '978-602-001-014-1', 'title' => 'Kumpulan Esai', 'category' => 'sastra', 'author' => 'Budi Santoso', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2017, 'pages' => 196],
            ['isbn' => '978-602-001-015-8', 'title' => 'Fisika untuk Kehidupan Sehari-hari', 'category' => 'sains', 'author' => 'Dewi Lestari', 'publisher' => 'Penerbit Sains Modern', 'year' => 2021, 'pages' => 330],
            ['isbn' => '978-602-001-016-5', 'title' => 'Kimia Dasar dan Percobaan', 'category' => 'sains', 'author' => 'Andi Prasetyo', 'publisher' => 'Penerbit Sains Modern', 'year' => 2020, 'pages' => 288],
            ['isbn' => '978-602-001-017-2', 'title' => 'Biologi', 'category' => 'sains', 'author' => 'Siti Aminah', 'publisher' => 'Penerbit Sains Modern', 'year' => 2022, 'pages' => 302],
            ['isbn' => '978-602-001-018-9', 'title' => 'Astronomi untuk Pelajar', 'category' => 'sains', 'author' => 'Rina Kusuma', 'publisher' => 'Penerbit Sains Modern', 'year' => 2019, 'pages' => 174],
            ['isbn' => '978-602-001-019-6', 'title' => 'Surat untuk Sahabat', 'category' => 'sastra', 'author' => 'Dewi Lestari', 'publisher' => 'Penerbit Sastra Kita', 'year' => 2024, 'pages' => 210],
            ['isbn' => '978-602-001-020-2', 'title' => 'Teknologi dan Masyarakat', 'category' => 'teknologi', 'author' => 'Hendra Wijaya', 'publisher' => 'Penerbit Teknologi Digital', 'year' => 2024, 'pages' => 268],
        ];

        foreach ($books as $book) {
            Book::updateOrCreate(
                ['isbn' => $book['isbn']],
                [
                    'title' => $book['title'],
                    'description' => $book['title'].' merupakan salah satu koleksi digital Perpustakaan Digital.',
                    'publication_year' => $book['year'],
                    'pages' => $book['pages'],
                    'cover' => null,
                    'file' => null,
                    'category_id' => $categoryIds[$book['category']] ?? null,
                    'author_id' => $authorIds[$book['author']] ?? null,
                    'publisher_id' => $publisherIds[$book['publisher']] ?? null,
                    'status' => Book::STATUS_AVAILABLE,
                ]
            );
        }
    }
}
