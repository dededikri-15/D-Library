<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationBookTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    /* ------------------------------------------------------------------
     | Buku
     * ----------------------------------------------------------------- */

    public function test_pustakawan_menambah_buku_mendapat_notifikasi_book_created(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->make();

        $this->actingAs($staff)->post(route('books.store'), [
            'title' => $book->title,
            'isbn' => '978-602-111-201-9',
            'publication_year' => 2024,
            'status' => Book::STATUS_AVAILABLE,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'book_created');
    }

    public function test_mengubah_buku_memberi_notifikasi_book_updated_dengan_judul(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create();

        $this->actingAs($staff)->patch(route('books.update', $book), [
            'title' => 'Judul Baru Hasil Edit',
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'book_updated');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame('Judul Baru Hasil Edit', $payload['subject']);
    }

    public function test_menyimpan_buku_tanpa_perubahan_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create();

        $this->actingAs($staff)->patch(route('books.update', $book), [
            'title' => $book->title,
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertNoActionLogged($staff, 'book_updated');
    }
}
