<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesBookUploadFixtures;
use Tests\TestCase;

class FileDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk publik disihkan, disk privat memakai array storage supaya test
        // tidak menyentuh storage/app/private yang sedang dipakai dev.
        Storage::fake('public');
        Storage::fake('local');
    }

    use CreatesLibraryUsers;
    use ProvidesBookUploadFixtures;

    public function test_remove_file_clears_column_and_deletes_pdf(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'file' => UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf'),
        ]));

        $book = Book::firstOrFail();
        $file = $book->file;

        $this->actingAs($this->staff())
            ->put(route('books.update', $book), $this->bookPayload(['remove_file' => 1]));

        $book->refresh();

        $this->assertNull($book->file);
        Storage::disk('local')->assertMissing($file);
    }

    /* ------------------------------------------------------------------
     | Task 6.9 — hapus berkas saat data dihapus
     * ----------------------------------------------------------------- */

    public function test_deleting_book_deletes_cover_and_pdf_from_disk(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'file' => UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf'),
        ]));

        $book = Book::firstOrFail();
        $cover = $book->cover;
        $file = $book->file;

        $this->actingAs($this->staff())
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        Storage::disk('public')->assertMissing($cover);
        Storage::disk('local')->assertMissing($file);
    }

    /* ------------------------------------------------------------------
     | Task 6.3 — foto penulis
     * ----------------------------------------------------------------- */

    public function test_author_photo_is_uploaded_and_replaced(): void
    {
        $this->actingAs($this->staff())->post(route('authors.store'), [
            'name' => 'Andrea Hirata',
            'photo' => UploadedFile::fake()->image('lama.jpg'),
        ]);

        $author = Author::firstOrFail();
        $this->assertNotNull($author->photo);
        Storage::disk('public')->assertExists($author->photo);

        $oldPath = $author->photo;

        $this->actingAs($this->staff())
            ->put(route('authors.update', $author), [
                'name' => 'Andrea Hirata',
                'photo' => UploadedFile::fake()->image('baru.jpg'),
            ]);

        $author->refresh();
        $this->assertNotSame($oldPath, $author->photo);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($author->photo);
    }
}
