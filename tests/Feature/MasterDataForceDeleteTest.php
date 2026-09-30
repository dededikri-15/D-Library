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
    use RefreshDatabase;

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    /**
     * Tiga jenis master data, plus kolom di `books` yang menunjuk ke sana.
     *
     * @return list<array{0: class-string<Model>, 1: string, 2: string, 3: string}>
     */
    public static function masterDataTypes(): array
    {
        return [
            'kategori' => [Category::class, 'kategori', 'categories', 'category_id'],
            'penulis' => [Author::class, 'penulis', 'authors', 'author_id'],
            'penerbit' => [Publisher::class, 'penerbit', 'publishers', 'publisher_id'],
        ];
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function model(string $class): Model
    {
        return $class::create(['name' => 'Data Master', 'slug' => 'data-master']);
    }

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

    /**
     * Cascade di database tidak mengenal storage. Tanpa penghapusan berkas di
     * sini, cover dan PDF setiap buku yang dihapus akan menggantung di disk
     * tanpa pernah dirujuk lagi — file yatim yang tidak terlihat tapi makan
     * tempat selamanya.
     */
    public function test_berkas_cover_dan_pdf_buku_ikut_terhapus(): void
    {
        $coverDisk = Book::coverDisk();
        $fileDisk = Book::bookFileDisk();

        Storage::fake($coverDisk);
        Storage::fake($fileDisk);

        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'cover' => 'covers/fiksi.jpg',
            'file' => 'books/fiksi.pdf',
        ]);

        Storage::disk($coverDisk)->put('covers/fiksi.jpg', 'gambar');
        Storage::disk($fileDisk)->put('books/fiksi.pdf', 'pdf');

        $this->actingAs($this->librarian())->delete(route('categories.destroy', $category));

        Storage::disk($coverDisk)->assertMissing('covers/fiksi.jpg');
        Storage::disk($fileDisk)->assertMissing('books/fiksi.pdf');
    }

    public function test_pesan_sesuai_menyebut_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(3)->create(['category_id' => $category->id]);

        $this->actingAs($this->librarian())
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('status', fn ($message) => str_contains($message, '3 buku'));
    }

    public function test_master_data_tanpa_buku_tetap_bisa_dihapus(): void
    {
        foreach (self::masterDataTypes() as [$class, , $routeBase]) {
            $row = $this->model($class);

            $this->actingAs($this->librarian())
                ->delete(route($routeBase.'.destroy', $row))
                ->assertRedirect(route($routeBase.'.index'))
                ->assertSessionHas('status')
                ->assertSessionMissing('error');

            $this->assertDatabaseMissing((new $class)->getTable(), ['id' => $row->id]);
        }
    }

    public function test_foto_penulis_ikut_terhapus_kalau_penulisnya_dihapus(): void
    {
        $disk = (string) config('perpustakaan.uploads.cover_disk');
        Storage::fake($disk);

        $author = Author::create(['name' => 'Seno', 'slug' => 'seno']);
        $author->forceFill(['photo' => 'authors/seno.jpg'])->save();
        Storage::disk($disk)->put('authors/seno.jpg', 'gambar');

        $this->actingAs($this->librarian())->delete(route('authors.destroy', $author));

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
        Storage::disk($disk)->assertMissing('authors/seno.jpg');
    }

    public function test_halaman_edit_menampilkan_tombol_hapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $author = Author::create(['name' => 'Pramoedya', 'slug' => 'pramoedya']);
        $publisher = Publisher::create(['name' => 'Gramedia', 'slug' => 'gramedia']);

        $librarian = $this->librarian();

        $cases = [
            [route('categories.edit', $category), route('categories.destroy', $category), 'kategori'],
            [route('authors.edit', $author), route('authors.destroy', $author), 'penulis'],
            [route('publishers.edit', $publisher), route('publishers.destroy', $publisher), 'penerbit'],
        ];

        foreach ($cases as [$editUrl, $destroyUrl, $label]) {
            $content = $this->actingAs($librarian)->get($editUrl)->assertOk()->getContent();

            $this->assertStringContainsString(
                'name="_method" value="DELETE"',
                $content,
                "Form hapus tidak ada di {$editUrl}.",
            );
            $this->assertStringContainsString(
                $destroyUrl,
                $content,
                "Form hapus di {$editUrl} tidak mengarah ke route hapus {$label}.",
            );
            $this->assertStringContainsString(
                '>Hapus '.$label.'<',
                $content,
                "Tombol hapus {$label} tidak ada di {$editUrl}.",
            );
        }
    }

    /**
     * Jumlah buku yang ikut terhapus harus tertulis di halaman SEBELUM tombol
     * diklik, dan jumlahnya harus sama dengan yang dihitung server. Kalau
     * angkanya berbeda, orang menekan tombol dengan informasi yang salah.
     */
    public function test_halaman_edit_menuliskan_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('2 buku', $content);
        $this->assertStringContainsString('ikut terhapus permanen', $content);
    }

    /**
     * Tombol hapus tidak boleh pernah dimatikan. Menahannya hanya karena
     * masih ada buku membuat pustakawan tidak bisa membereskan data yang
     * salah input tanpa membuka tiap buku satu per satu.
     */
    public function test_tombol_hapus_tidak_pernah_dimatikan(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $edit = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus kategori</',
            $edit,
        );

        $index = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus</',
            $index,
        );
    }

    /**
     * Di halaman daftar, Hapus berdiri sendiri di kolomnya sendiri — terpisah
     * dari Edit. Kalau masih digabung di satu kolom "Aksi", dua tombol
     * berdampingan itu mudah salah klik, dan yang salah klik biasanya Hapus.
     */
    public function test_daftar_mempunyai_kolom_hapus_tersendiri(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<th[^>]*>Hapus<\/th>/', $content);
        $this->assertMatchesRegularExpression('/<th[^>]*>Edit<\/th>/', $content);
        $this->assertStringContainsString(
            route('categories.destroy', $category),
            $content,
            'Form hapus di halaman daftar harus mengarah ke route hapus.',
        );
    }

    /**
     * Di tabel daftar, akibatnya harus terbaca tanpa perlu menekan apa pun:
     * jumlah buku yang ikut hilang ditulis di baris itu sendiri, dan
     * kalimat konfirmasinya menyebut hal yang sama.
     */
    public function test_daftar_menuliskan_berapa_buku_yang_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('2 buku ikut terhapus', $content);
        $this->assertStringContainsString('2 buku ikut terhapus permanen, beserta file dan riwayat peminjamannya.', $content);

        // Tanda kutip harus tampil sebagai karakter, bukan `&quot;`. Nilai
        // `data-confirm` di-escape Blade ke `&quot;` di HTML, dan browser
        // membacanya kembali jadi `"` — kalau `&quot;` juga ditulis manual
        // di dalam Blade, teks itu yang muncul di dialog.
        $this->assertStringNotContainsString('&quot;', html_entity_decode($content, ENT_QUOTES));
    }

    public function test_daftar_menampilkan_catatan_hanya_pada_baris_yang_dipakai(): void
    {
        $dipakai = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $dipakai->id]);
        Category::create(['name' => 'Sains', 'slug' => 'sains']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        // Baris yang tidak dipakai bukunya tidak boleh ikut menyebut jumlah
        // buku, jadi kalimat ini harus muncul tepat satu kali.
        $this->assertSame(1, substr_count(
            $content,
            '2 buku ikut terhapus permanen, beserta file dan riwayat peminjamannya.',
        ));
        $this->assertStringContainsString('Sains', $content);
    }

    public function test_area_hapus_tidak_boleh_di_dalam_form_edit(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        $html = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        // <form> bersarang = browser membuang tag form kedua, dan tombol hapus
        // ikut jadi milik form update. Efeknya, menekan "Hapus kategori" bisa
        // mengirim update, bukan delete.
        preg_match_all('/<(\/?)form\b[^>]*>/i', $html, $matches, PREG_SET_ORDER);

        $depth = 0;
        foreach ($matches as $match) {
            if ($match[1] === '/') {
                $depth = max(0, $depth - 1);

                continue;
            }

            $this->assertSame(0, $depth, "Ada <form> bersarang: {$match[0]}");
            $depth++;
        }
    }

    public function test_anggota_tidak_bisa_menghapus_master_data(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ANGGOTA]))
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
