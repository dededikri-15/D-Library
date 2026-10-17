<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\AssertsActionLogged;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationMasterDataTest extends TestCase
{
    use AssertsActionLogged;
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_menghapus_buku_memberi_notifikasi_book_deleted(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create(['title' => 'Buku Akan Dihapus']);

        $this->actingAs($staff)->delete(route('books.destroy', $book))->assertRedirect();

        $this->assertActionLogged($staff, 'book_deleted');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame('Buku Akan Dihapus', $payload['subject']);
    }

    /* ------------------------------------------------------------------
     | Master data: kategori, penulis, penerbit
     * ----------------------------------------------------------------- */

    public function test_kategori_baru_update_dan_hapus_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('categories.store'), ['name' => 'Sains'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_created');

        $category = Category::where('name', 'Sains')->firstOrFail();

        $this->actingAs($staff)->patch(route('categories.update', $category), ['name' => 'Sains Populer'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_updated');

        $this->actingAs($staff)->delete(route('categories.destroy', $category))
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_deleted');
    }

    public function test_quick_add_kategori_juga_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->postJson(route('categories.quick'), ['name' => 'Novel'])
            ->assertOk();

        $this->assertActionLogged($staff, 'category_created');
    }

    public function test_penulis_dan_penerbit_baru_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('authors.store'), ['name' => 'Penulis Uji'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'author_created');

        $this->actingAs($staff)->postJson(route('publishers.quick'), ['name' => 'Penerbit Uji'])
            ->assertOk();
        $this->assertActionLogged($staff, 'publisher_created');

        $author = Author::where('name', 'Penulis Uji')->firstOrFail();
        $this->actingAs($staff)->delete(route('authors.destroy', $author))->assertRedirect();
        $this->assertActionLogged($staff, 'author_deleted');

        $publisher = Publisher::where('name', 'Penerbit Uji')->firstOrFail();
        $this->actingAs($staff)->delete(route('publishers.destroy', $publisher))->assertRedirect();
        $this->assertActionLogged($staff, 'publisher_deleted');
    }
}
