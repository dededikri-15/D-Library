<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class BookFileUploadTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk publik disihkan, disk privat memakai array storage supaya test
        // tidak menyentuh storage/app/private yang sedang dipakai dev.
        Storage::fake('public');
        Storage::fake('local');
    }

    /**
     * Data dasar yang sah untuk POST /buku.
     *
     * @return array<string, mixed>
     */
    protected function bookPayload(array $overrides = []): array
    {
        $book = Book::factory()->make();

        return array_merge([
            'title' => 'Buku Tanpa Berkas',
            'isbn' => '978-602-111-111-1',
            'publication_year' => 2024,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
            'status' => Book::STATUS_AVAILABLE,
        ], $overrides);
    }

    /* ------------------------------------------------------------------
     | Task 6.6 — upload cover
     * ----------------------------------------------------------------- */

    public function test_cover_is_uploaded_to_public_disk(): void
    {
        $response = $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'cover' => UploadedFile::fake()->image('cover.jpg', 400, 600),
            ]));

        $book = Book::firstOrFail();
        $response->assertRedirect(route('books.show', $book));

        $this->assertNotNull($book->cover);
        $this->assertStringStartsWith('covers/', $book->cover);
        Storage::disk('public')->assertExists($book->cover);
    }

    public function test_cover_filename_is_regenerated_not_taken_from_user(): void
    {
        $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'cover' => UploadedFile::fake()->image('../../etc/passwd.jpg'),
            ]));

        $book = Book::firstOrFail();

        // Path tidak boleh keluar dari folder dan tidak boleh menyimpan nama asli.
        $this->assertStringStartsWith('covers/', $book->cover);
        $this->assertStringNotContainsString('..', $book->cover);
        $this->assertStringNotContainsString('passwd', $book->cover);
    }

    public function test_non_image_cover_is_rejected(): void
    {
        $response = $this->actingAs($this->staff())
            ->post(route('books.store'), $this->bookPayload([
                'cover' => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
            ]));

        $response->assertSessionHasErrors('cover');
        $this->assertDatabaseCount('books', 0);
    }
}
