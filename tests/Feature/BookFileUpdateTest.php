<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesBookUploadFixtures;
use Tests\TestCase;

class BookFileUpdateTest extends TestCase
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

    /* ------------------------------------------------------------------
     | Task 6.8 — edit & replace berkas
     * ----------------------------------------------------------------- */

    public function test_uploading_new_cover_replaces_and_deletes_old_file(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'cover' => UploadedFile::fake()->image('lama.jpg'),
        ]));

        $book = Book::firstOrFail();
        $oldPath = $book->cover;

        $this->actingAs($this->staff())
            ->put(route('books.update', $book), $this->bookPayload([
                'title' => 'Judul Diubah',
                'cover' => UploadedFile::fake()->image('baru.jpg'),
            ]));

        $book->refresh();

        $this->assertNotSame($oldPath, $book->cover);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($book->cover);
    }

    public function test_editing_without_uploading_keeps_existing_files(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'file' => UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf'),
        ]));

        $book = Book::firstOrFail();
        $cover = $book->cover;
        $file = $book->file;

        $this->actingAs($this->staff())
            ->put(route('books.update', $book), $this->bookPayload(['title' => 'Judul Diubah']));

        $book->refresh();

        $this->assertSame($cover, $book->cover);
        $this->assertSame($file, $book->file);
        Storage::disk('public')->assertExists($cover);
        Storage::disk('local')->assertExists($file);
    }

    public function test_remove_cover_clears_column_and_deletes_file(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]));

        $book = Book::firstOrFail();
        $cover = $book->cover;

        $this->actingAs($this->staff())
            ->put(route('books.update', $book), $this->bookPayload(['remove_cover' => 1]));

        $book->refresh();

        $this->assertNull($book->cover);
        Storage::disk('public')->assertMissing($cover);
    }
}
