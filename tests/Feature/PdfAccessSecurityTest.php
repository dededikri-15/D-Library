<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    /* ------------------------------------------------------------------
     | Task 7.10 — proteksi file PDF
     * ----------------------------------------------------------------- */

    public function test_member_cannot_read_another_members_pdf(): void
    {
        $book = Book::factory()->create(['file' => 'books/digital.pdf']);

        $owner = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $owner->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $other = User::factory()->anggota()->create();

        $this->actingAs($other)
            ->get(route('books.file', $book))
            ->assertForbidden();
    }

    public function test_pdf_access_cannot_be_bypassed_through_the_storage_route(): void
    {
        $book = Book::factory()->create(['file' => 'books/rahasia.pdf']);

        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        // Bahkan anggota yang berhak pun tidak boleh mengambil PDF lewat
        // /storage/{path} — berkasnya harus lewat controller yang memeriksa.
        $response = $this->actingAs($member)->get('/storage/books/rahasia.pdf');

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
