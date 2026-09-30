<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tombol hapus di halaman edit master data (kategori, penulis, penerbit).
 *
 * Yang diuji di sini bukan "tombolnya ada", tapi apa yang terjadi ketika
 * ditekan. Alasannya: `books.category_id` (dan `author_id`, `publisher_id`)
 * memakai `cascadeOnDelete()`. Artinya menghapus satu kategori bisa menghapus
 * Attached seluruh buku di dalamnya — beserta `loans`, `favorites`, dan
 * `reading_histories` yang menempel ke buku-buku itu — tanpa ada yang
 * menyadarinya. Jadi hapus master data harus DITOLAK selama masih ada buku
 * yang memakainya, dan orang harus diberi tahu berapa banyak.
 */
class MasterDataDeleteGuardTest extends TestCase
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

    public function test_master_data_yang_masih_dipakai_buku_tidak_bisa_dihapus(): void
    {
        foreach (self::masterDataTypes() as [$class, $label, $routeBase, $column]) {
            $row = $this->model($class);

            $books = Book::factory()->count(3)->create([$column => $row->id]);

            $this->actingAs($this->librarian())
                ->delete(route($routeBase.'.destroy', $row))
                ->assertRedirect(route($routeBase.'.index'))
                ->assertSessionHas('error', fn ($message) => str_contains($message, '3 buku'));

            // Datanya harus tetap ada. Kalau tidak, tiga buku di atas ikut
            // terhapus oleh cascade — dan itu persis skenario yang harus dicegah.
            $this->assertDatabaseHas((new $class)->getTable(), ['id' => $row->id]);

            foreach ($books as $book) {
                $this->assertDatabaseHas('books', ['id' => $book->id]);
            }

            $this->assertStringContainsString(
                $label,
                session('error'),
                "Pesan untuk {$label} harus menyebut jenis datanya, bukan kalimat generik.",
            );
        }
    }

    public function test_buku_yang_memakai_master_data_tidak_ikut_terhapus(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create(['category_id' => $category->id]);
        Loan::factory()->create(['book_id' => $book->id]);

        $this->actingAs($this->librarian())
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
        $this->assertDatabaseCount('loans', 1);
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

    public function test_foto_penulis_tidak_terhapus_kalau_hapusnya_ditolak(): void
    {
        Storage::fake((string) config('perpustakaan.uploads.cover_disk'));

        $author = Author::create(['name' => 'Pramoedya', 'slug' => 'pramoedya']);
        $author->forceFill(['photo' => 'authors/foto.jpg'])->save();

        Storage::disk((string) config('perpustakaan.uploads.cover_disk'))->put('authors/foto.jpg', 'gambar');

        Book::factory()->create(['author_id' => $author->id]);

        $this->actingAs($this->librarian())->delete(route('authors.destroy', $author));

        $this->assertDatabaseHas('authors', ['id' => $author->id]);

        // Berkasnya harus utuh. Menghapus foto sementara penulisnya masih ada
        // di database berarti halaman editnya langsung menampilkan gambar rusak.
        Storage::disk((string) config('perpustakaan.uploads.cover_disk'))
            ->assertExists('authors/foto.jpg');
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

    public function test_tombol_hapus_dimatikan_saat_masih_dipakai_buku(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        // Jumlah buku yang tampil harus sama dengan yang dihitung server saat
        // hapus, supaya orang tidak pernah diberi angka yang berbeda antara
        // "sebelum" dan "sesudah" menekan.
        $this->assertStringContainsString('masih dipakai 2 buku', $content);
        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus kategori</',
            $content,
            'Tombol hapus harus nonaktif selama kategori masih dipakai buku.',
        );
    }

    public function test_tombol_hapus_aktif_saat_belum_dipakai_buku(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus kategori</',
            $content,
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

        $this->assertMatchesRegularExpression(
            '/<th[^>]*>Hapus<\/th>/',
            $content,
            'Hapus harus punya kolom sendiri di header tabel.',
        );
        $this->assertMatchesRegularExpression(
            '/<th[^>]*>Edit<\/th>/',
            $content,
            'Edit harus punya kolom sendiri di header tabel.',
        );
        $this->assertStringContainsString(
            route('categories.destroy', $category),
            $content,
            'Form hapus di halaman daftar harus mengarah ke route hapus.',
        );
    }

    /**
     * Tombol yang mati harus menjelaskan dirinya sendiri di halaman, bukan cuma
     * lewat atribut `title` yang tidak pernah muncul di layar sentuh.
     */
    public function test_daftar_menuliskan_alasan_saat_hapus_dimatikan(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $category->id]);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*type="submit"[^>]*disabled[^>]*>Hapus</',
            $content,
            'Tombol hapus di daftar harus nonaktif selama masih dipakai buku.',
        );
        $this->assertStringContainsString(
            'Dipakai 2 buku',
            $content,
            'Alasan tombolnya mati harus tertulis di halaman, bukan hanya di tooltip.',
        );
    }

    public function test_daftar_menampilkan_alasan_hanya_pada_baris_yang_dipakai(): void
    {
        $dipakai = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $dipakai->id]);
        Category::create(['name' => 'Sains', 'slug' => 'sains']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($content, 'Dipakai 2 buku'));
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

    public function test_pesan_gagal_hapus_dirender_sebagai_toast_merah(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->librarian())
            ->from(route('categories.index'))
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('tidak bisa dihapus', $content);
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
