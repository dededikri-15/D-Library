<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookFileUploadTest extends TestCase
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

    protected function staff(): User
    {
        return User::factory()->pustakawan()->create();
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

    public function test_deleting_author_deletes_photo(): void
    {
        $this->actingAs($this->staff())->post(route('authors.store'), [
            'name' => 'Andrea Hirata',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $author = Author::firstOrFail();
        $photo = $author->photo;

        $this->actingAs($this->staff())->delete(route('authors.destroy', $author));

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_author_photo_must_be_an_image(): void
    {
        $this->actingAs($this->staff())
            ->post(route('authors.store'), [
                'name' => 'Salah Upload',
                'photo' => UploadedFile::fake()->create('script.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('authors', 0);
    }

    /* ------------------------------------------------------------------
     | Hak akses file PDF
     * ----------------------------------------------------------------- */

    protected function bookWithPdf(?array $loanStatus = null): Book
    {
        $book = Book::factory()->create([
            'file' => 'books/digital.pdf',
        ]);

        Storage::disk('local')->put('books/digital.pdf', '%PDF-1.4 test');

        if ($loanStatus !== null) {
            Loan::factory()->create([
                'book_id' => $book->id,
                'user_id' => User::factory()->anggota()->create()->id,
                'status' => $loanStatus,
            ]);
        }

        return $book;
    }

    public function test_guest_cannot_read_pdf(): void
    {
        $book = $this->bookWithPdf();

        $this->get(route('books.file', $book))->assertRedirect(route('login'));
        $this->get(route('books.read', $book))->assertRedirect(route('login'));
    }

    public function test_staff_can_read_pdf(): void
    {
        $book = $this->bookWithPdf();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee($book->title);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.file', $book))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="'.$book->title.'.pdf"');
    }

    public function test_member_with_active_loan_can_read_pdf(): void
    {
        $book = Book::factory()->create(['file' => 'books/digital.pdf']);
        Storage::disk('local')->put('books/digital.pdf', '%PDF-1.4 test');

        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('books.file', $book))
            ->assertOk();
    }

    public function test_member_without_loan_cannot_read_pdf(): void
    {
        $book = $this->bookWithPdf();

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.file', $book))
            ->assertForbidden();
    }

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
