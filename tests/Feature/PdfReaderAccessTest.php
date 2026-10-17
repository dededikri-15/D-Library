<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfReaderAccessTest extends TestCase
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
}
