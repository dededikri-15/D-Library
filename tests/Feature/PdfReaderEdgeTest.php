<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\Concerns\ProvidesBookUploadFixtures;
use Tests\TestCase;

class PdfReaderEdgeTest extends TestCase
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

    public function test_member_whose_loan_was_returned_loses_access(): void
    {
        $book = Book::factory()->create(['file' => 'books/digital.pdf']);
        Storage::disk('local')->put('books/digital.pdf', '%PDF-1.4 test');

        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_RETURNED,
        ]);

        $this->actingAs($member)
            ->get(route('books.file', $book))
            ->assertForbidden();
    }

    public function test_reading_page_redirects_when_book_has_no_pdf(): void
    {
        $book = Book::factory()->create(['file' => null]);

        $this->actingAs($this->staff())
            ->get(route('books.read', $book))
            ->assertRedirect(route('books.show', $book));
    }

    public function test_reading_page_returns_404_when_file_missing_on_disk(): void
    {
        // Kolom terisi tapi berkasnya hilang dari disk.
        $book = Book::factory()->create(['file' => 'books/hilang.pdf']);

        $this->actingAs($this->staff())
            ->get(route('books.file', $book))
            ->assertNotFound();
    }

    public function test_pdf_is_not_reachable_through_a_public_url(): void
    {
        $book = $this->bookWithPdf();

        // Disk `local` (penyimpanan PDF) punya `serve => true` tapi TIDAK
        // ditandai `visibility: public`, jadi Laravel hanya melayaninya lewat
        // signed URL. Request tanpa signature harus ditolak: 403 saat
        // development/testing, 404 di production
        // (lihat Illuminate\Filesystem\ServeFile::hasValidSignature).
        $response = $this->get('/storage/'.$book->file);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertStringNotContainsString('%PDF', $response->getContent());
    }

    public function test_cover_is_stored_on_the_public_disk(): void
    {
        $this->actingAs($this->staff())->post(route('books.store'), $this->bookPayload([
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]));

        $book = Book::firstOrFail();

        $this->assertStringStartsWith('covers/', $book->cover);
        Storage::disk('public')->assertExists($book->cover);
    }
}
