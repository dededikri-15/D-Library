<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Fiksi', 'description' => 'Karya imajinatif seperti novel dan cerita pendek.'],
            ['name' => 'Pendidikan', 'description' => 'Buku ajar dan panduan belajar.'],
            ['name' => 'Teknologi', 'description' => 'Ilmu komputer, rekayasa, dan teknologi digital.'],
            ['name' => 'Sejarah', 'description' => 'Kisah masa lalu dan perkembangan peradaban.'],
            ['name' => 'Agama', 'description' => 'Pengetahuan keagamaan dan spiritual.'],
            ['name' => 'Sastra', 'description' => 'Karya sastra, puisi, dan esai.'],
            ['name' => 'Sains', 'description' => 'Ilmu pengetahuan alam dan terapan.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                ]
            );
        }
    }
}
