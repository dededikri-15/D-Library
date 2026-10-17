<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ForceDeleteEditPageTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_foto_penulis_ikut_terhapus_kalau_penulisnya_dihapus(): void
    {
        $disk = (string) config('perpustakaan.uploads.cover_disk');
        Storage::fake($disk);

        $author = Author::create(['name' => 'Seno', 'slug' => 'seno']);
        $author->forceFill(['photo' => 'authors/seno.jpg'])->save();
        Storage::disk($disk)->put('authors/seno.jpg', 'gambar');

        $this->actingAs($this->librarian())->delete(route('authors.destroy', $author));

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
        Storage::disk($disk)->assertMissing('authors/seno.jpg');
    }

    public function test_halaman_edit_menampilkan_tombol_hapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $author = Author::create(['name' => 'Pramoedya', 'slug' => 'pramoedya']);
        $publisher = Publisher::create(['name' => 'Gramedia', 'slug' => 'gramedia']);

        $librarian = $this->librarian();

        $cases = [
            [route('categories.edit', $category), route('categories.destroy', $category), 'kategori'],
            [route('authors.edit', $author), route('authors.destroy', $author), 'penulis'],
            [route('publishers.edit', $publisher), route('publishers.destroy', $publisher), 'penerbit'],
        ];

        foreach ($cases as [$editUrl, $destroyUrl, $label]) {
            $content = $this->actingAs($librarian)->get($editUrl)->assertOk()->getContent();

            $this->assertStringContainsString(
                'name="_method" value="DELETE"',
                $content,
                "Form hapus tidak ada di {$editUrl}.",
            );
            $this->assertStringContainsString(
                $destroyUrl,
                $content,
                "Form hapus di {$editUrl} tidak mengarah ke route hapus {$label}.",
            );
            $this->assertStringContainsString(
                '>Hapus '.$label.'<',
                $content,
                "Tombol hapus {$label} tidak ada di {$editUrl}.",
            );
        }
    }

    /**
     * Jumlah buku yang ikut terhapus harus tertulis di halaman SEBELUM tombol
     * diklik, dan jumlahnya harus sama dengan yang dihitung server. Kalau
     * angkanya berbeda, orang menekan tombol dengan informasi yang salah.
     */
    public function test_halaman_edit_menuliskan_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('2 buku', $content);
        $this->assertStringContainsString('ikut terhapus permanen', $content);
    }
}
