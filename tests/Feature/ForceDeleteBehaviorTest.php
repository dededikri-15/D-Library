<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForceDeleteBehaviorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cascade di database tidak mengenal storage. Tanpa penghapusan berkas di
     * sini, cover dan PDF setiap buku yang dihapus akan menggantung di disk
     * tanpa pernah dirujuk lagi — file yatim yang tidak terlihat tapi makan
     * tempat selamanya.
     */
    public function test_berkas_cover_dan_pdf_buku_ikut_terhapus(): void
    {
        $coverDisk = Book::coverDisk();
        $fileDisk = Book::bookFileDisk();

        Storage::fake($coverDisk);
        Storage::fake($fileDisk);

        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'cover' => 'covers/fiksi.jpg',
            'file' => 'books/fiksi.pdf',
        ]);

        Storage::disk($coverDisk)->put('covers/fiksi.jpg', 'gambar');
        Storage::disk($fileDisk)->put('books/fiksi.pdf', 'pdf');

        $this->actingAs($this->librarian())->delete(route('categories.destroy', $category));

        Storage::disk($coverDisk)->assertMissing('covers/fiksi.jpg');
        Storage::disk($fileDisk)->assertMissing('books/fiksi.pdf');
    }

    public function test_pesan_sesuai_menyebut_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $this->actingAs($this->librarian())
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('status', fn ($message) => str_contains($message, '3 buku'));
    }

    public function test_master_data_tanpa_buku_tetap_bisa_dihapus(): void
    {
        foreach (self::masterDataTypes() as [$class, , $routeBase]) {
            $row = $this->model($class);

            $this->actingAs($this->librarian())
                ->delete(route($routeBase.'.destroy', $row))
                ->assertRedirect(route($routeBase.'.index'))
                ->assertSessionHas('status')
                ->assertSessionMissing('error');

            $this->assertDatabaseMissing((new $class)->getTable(), ['id' => $row->id]);
        }
    }
}
