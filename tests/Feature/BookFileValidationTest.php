<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesBookUploadFixtures;
use Tests\TestCase;

class BookFileValidationTest extends TestCase
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

    public function test_cover_larger_than_limit_is_rejected(): void
    {
        config()->set('perpustakaan.uploads.cover_max_kb', 10);

        $response = $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'cover' => UploadedFile::fake()->image('besar.jpg')->size(50),
            ]));

        $response->assertSessionHasErrors('cover');
        $this->assertDatabaseCount('books', 0);
    }

    /* ------------------------------------------------------------------
     | Task 6.7 — upload file PDF
     * ----------------------------------------------------------------- */

    public function test_pdf_is_stored_on_private_disk(): void
    {
        $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'file' => UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf'),
            ]));

        $book = Book::firstOrFail();

        $this->assertNotNull($book->file);
        Storage::disk('local')->assertExists($book->file);

        // Keamanan utama: PDF tidak boleh bocor ke disk publik.
        Storage::disk('public')->assertMissing($book->file);
    }

    public function test_file_renamed_to_pdf_extension_that_is_not_a_real_pdf_is_rejected(): void
    {
        $response = $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                // Ekstensi .pdf, isi HTML — harus ditolak oleh aturan mimetypes.
                'file' => UploadedFile::fake()->create('jebakan.pdf', 10, 'text/html'),
            ]));

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 0);
    }

    public function test_file_larger_than_limit_is_rejected(): void
    {
        config()->set('perpustakaan.uploads.book_file_max_kb', 100);

        $response = $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'file' => UploadedFile::fake()->create('besar.pdf', 500, 'application/pdf'),
            ]));

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('books', 0);
    }

    public function test_cover_and_pdf_are_both_optional(): void
    {
        $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload())
            ->assertSessionHasNoErrors();

        $book = Book::firstOrFail();
        $this->assertNull($book->cover);
        $this->assertNull($book->file);
    }
}
