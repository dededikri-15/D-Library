<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publishers = [
            ['name' => 'Penerbit Cendana', 'address' => 'Jl. Merdeka No. 10, Jakarta', 'website' => 'https://penerbitcendana.test'],
            ['name' => 'Penerbit Ilmiah Nusantara', 'address' => 'Jl. Pendidikan No. 25, Bandung', 'website' => 'https://ilmuahnusantara.test'],
            ['name' => 'Penerbit Teknologi Digital', 'address' => 'Jl. Inovasi No. 7, Surabaya', 'website' => 'https://teknologidigital.test'],
            ['name' => 'Penerbit Sejarah Nusantara', 'address' => 'Jl. Arsip No. 3, Yogyakarta', 'website' => 'https://sejarahnusantara.test'],
            ['name' => 'Penerbit Sastra Kita', 'address' => 'Jl. Puisi No. 14, Bandung', 'website' => 'https://sastrakita.test'],
            ['name' => 'Penerbit Sains Modern', 'address' => 'Jl. Sains No. 5, Jakarta', 'website' => 'https://sainsmodern.test'],
        ];

        foreach ($publishers as $publisher) {
            Publisher::updateOrCreate(['name' => $publisher['name']], $publisher);
        }
    }
}
