<?php

namespace Tests\Concerns;

use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar jenis master data + pembuat baris ujinya — dipakai bersama oleh
 * kelas hasil pemecahan MasterDataForceDeleteTest.
 */
trait ProvidesMasterDataFixtures
{
    /**
     * Tiga jenis master data, plus kolom di `books` yang menunjuk ke sana.
     *
     * @return list<array{0: class-string<Model>, 1: string, 2: string, 3: string}>
     */
    public static function masterDataTypes(): array
    {
        return [
            'kategori' => [Category::class, 'kategori', 'categories', 'category_id'],
            'penulis' => [Author::class, 'penulis', 'authors', 'author_id'],
            'penerbit' => [Publisher::class, 'penerbit', 'publishers', 'publisher_id'],
        ];
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function model(string $class): Model
    {
        return $class::create(['name' => 'Data Master', 'slug' => 'data-master']);
    }
}
