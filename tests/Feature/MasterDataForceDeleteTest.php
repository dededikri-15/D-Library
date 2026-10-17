<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

/**
 * Hapus master data (kategori, penulis, penerbit) yang masih dipakai buku.
 *
 * Yang diuji di sini bukan "tombolnya ada" atau "hapus tidak ditolak", tapi
 * apa yang terjadi pada isi dan berkasnya. Alasannya: `books.category_id`
 * (dan `author_id`, `publisher_id`) memakai `cascadeOnDelete()`, jadi
 * menghapus satu kategori menghapus seluruh buku di dalamnya — beserta
 * `loans`, `favorites`, dan `reading_histories` yang menempel ke buku-buku
 * itu.
 *
 * Permanen, tidak ada undo, dan tidak ada yang memberi tahu kalau tidak
 *ditanyakan. Karena itu dua hal wajib proved di sini:
 *
 * 1. Hasilnya benar-benar terjadi (buku, riwayat, dan berkasnya hilang).
 * 2. Jumlah yang hilang itu disampaikan ke orang yang menekan tombol, lewat
 *    pesan sukses dan dialog konfirmasi — bukan hanya hal yang diam-diam
 *    diketahui backend.
 */
class MasterDataForceDeleteTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_master_data_yang_masih_dipakai_buku_bisa_dihapus(): void
    {
        foreach (self::masterDataTypes() as [$class, $label, $routeBase, $column]) {
            $row = $this->model($class);

            $books = Book::factory()->count(3)->create([$column => $row->id]);

            $this->actingAs($this->librarian())
                ->delete(route($routeBase.'.destroy', $row))
                ->assertRedirect(route($routeBase.'.index'))
                ->assertSessionHas('status', fn ($message) => str_contains(mb_strtolower($message), $label))
                ->assertSessionMissing('error');

            $this->assertDatabaseMissing((new $class)->getTable(), ['id' => $row->id]);

            foreach ($books as $book) {
                $this->assertDatabaseMissing('books', ['id' => $book->id]);
            }
        }
    }

    /**
     * Cascade dua tingkat: master data -> buku -> riwayat yang menempel ke
     * buku. Kalau `loans` tidak ikut hilang, pustakawan akan menemukan baris
     * peminjaman yang menunjuk buku yang tidak ada.
     */
    public function test_riwayat_buku_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create(['category_id' => $category->id]);
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        Loan::factory()->create(['book_id' => $book->id, 'user_id' => $member->id]);
        Favorite::factory()->create(['book_id' => $book->id, 'user_id' => $member->id]);
        ReadingHistory::factory()->create(['book_id' => $book->id, 'user_id' => $member->id]);

        $this->actingAs($this->librarian())
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('favorites', 0);
        $this->assertDatabaseCount('reading_histories', 0);
    }
}
