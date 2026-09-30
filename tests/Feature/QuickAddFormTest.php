<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Quick add kategori/penulis/penerbit di form buku.
 *
 * Latar belakang bug yang diperbaiki di sini, karena gejalanya sama sekali
 * tidak berkaitan dengan "quick add".
 *
 * Ketiga modal quick-add sebelumnya ditulis DI DALAM `<form>` utama buku. HTML
 * tidak mengizinkan itu, dan browser "memperbaiki" markup-nya sendiri dengan
 * membuang tag `<form>` kedua beserta seluruh atributnya (`action`,
 * `data-quick-form`, `data-quick-target`). Dua akibatnya:
 *
 * 1. Input `name` yang `required` di dalam modal jadi dimiliki form utama
 *    buku. Modal yang tertutup berarti `display: none`, dan browser berbasis
 *    Chromium menolak mengirim form yang punya kontrol invalid tapi tidak bisa
 *    difokuskan: "An invalid form control with name='name' is not focusable".
 *    Tombol "Simpan perubahan" diklik, tidak ada yang terjadi, tidak ada pesan
 *    di layar — errornya hanya tertulis di console. Form buku praktis tidak
 *    bisa disimpan.
 *
 * 2. `initQuickAdd()` mencari `[data-quick-form]`, dan karena atribut itu ikut
 *    hilang bersama tagnya, tidak ketemu apa-apa. Tombol "Simpan" di dalam
 *    modal adalah submit button milik form utama buku, jadi yang terkirim
 *    adalah UPDATE buku, bukan POST quick add.
 *
 * Test di bawah menjaga bentuk HTML-nya, karena itu bagian yang tidak akan
 * terlihat dari test yang hanya menekan route dan melihat status 200.
 */
class QuickAddFormTest extends TestCase
{
    use RefreshDatabase;

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    /**
     * Tag `<form>` yang muncul di markup mentah saat form lain belum ditutup.
     *
     * Pentingnya dipindai dari STRING, bukan dari DOM. DOMDocument (libxml)
     * longgar: dia tetap membuat elemen `<form>` di dalam `<form>`, persis
     * seperti browser justru membuangnya. Kalau test memakai DOM untuk mencari
     * form bersarang, test-nya akan hijau untuk markup yang salah.
     *
     * @return list<string>
     */
    private function nestedFormTags(string $html): array
    {
        preg_match_all('/<(\/?)form\b[^>]*>/i', $html, $matches, PREG_SET_ORDER);

        $nested = [];
        $depth = 0;

        foreach ($matches as $match) {
            if ($match[1] === '/') {
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($depth > 0) {
                $nested[] = $match[0];
            }

            $depth++;
        }

        return $nested;
    }

    private function parse(string $html): DOMDocument
    {
        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<!DOCTYPE html><html><body>'.$html.'</body></html>');
        libxml_clear_errors();

        return $document;
    }

    /**
     * Halaman yang memuat form buku + tiga modal quick-add.
     *
     * @return list<array{0: string}>
     */
    public static function bookFormPages(): array
    {
        return [
            'form tambah buku' => ['books.create'],
            'form edit buku' => ['books.edit'],
        ];
    }

    private function bookFormPage(string $routeName): string
    {
        $book = Book::factory()->create();

        $parameters = $routeName === 'books.edit' ? ['book' => $book] : [];

        return $this->actingAs($this->librarian())
            ->get(route($routeName, $parameters))
            ->assertOk()
            ->getContent();
    }

    #[DataProvider('bookFormPages')]
    public function test_tidak_ada_form_bersarang(string $routeName): void
    {
        $this->assertSame(
            [],
            $this->nestedFormTags($this->bookFormPage($routeName)),
            'Ada <form> di dalam <form>. Browser membuang tag form kedua beserta '
            .'atributnya, sehingga field di dalamnya milik form luar dan form '
            .'buku tidak bisa dikirim.',
        );
    }

    #[DataProvider('bookFormPages')]
    public function test_form_quick_add_adalah_form_sendiri(string $routeName): void
    {
        $document = $this->parse($this->bookFormPage($routeName));
        $xpath = new DOMXPath($document);

        // Tiga form quick-add, masing-masing form utuh sendiri. Kalau ada form
        // bersarang, atribut `data-quick-form` ini justru hilang bersama tag
        // yang dibuang browser — jadi jumlahnya jadi nol di halaman yang
        // quick add-nya paling rusak.
        $this->assertSame(
            3,
            $xpath->query('//form[@data-quick-form]')->length,
            'Harus ada 3 form quick-add (kategori, penulis, penerbit) di '
            .'halaman form buku.',
        );

        $expected = [
            'quick-category-name' => ['category_id', route('categories.quick')],
            'quick-author-name' => ['author_id', route('authors.quick')],
            'quick-publisher-name' => ['publisher_id', route('publishers.quick')],
        ];

        foreach ($expected as $inputId => [$selectId, $action]) {
            $input = $document->getElementById($inputId);
            $this->assertInstanceOf(DOMElement::class, $input, "Input #{$inputId} tidak dirender.");

            // Form yang MEMILIKI input ini harus form quick-add itu sendiri,
            // bukan form buku. Inilah yang membuat `required` di dalam modal
            // tidak ikut memblokir submit form buku.
            $owners = $xpath->query('//form[.//input[@id="'.$inputId.'"]]');
            $this->assertSame(
                1,
                $owners->length,
                "Input #{$inputId} harus dimiliki tepat satu form.",
            );

            $owner = $owners->item(0);

            $this->assertSame(
                $action,
                $owner->getAttribute('action'),
                "Form quick-add untuk #{$inputId} harus mengarah ke {$action}.",
            );
            $this->assertSame(
                $selectId,
                $owner->getAttribute('data-quick-target'),
                "Form quick-add untuk #{$inputId} harus mengisi dropdown {$selectId}.",
            );
            $this->assertTrue(
                $owner->hasAttribute('data-quick-form'),
                "Form quick-add untuk #{$inputId} harus punya data-quick-form, "
                .'tanpanya initQuickAdd() tidak pernah berjalan.',
            );
            $this->assertTrue(
                $owner->getElementsByTagName('dialog')->length === 0,
                "Form quick-add untuk #{$inputId} tidak boleh dibungkus <dialog> "
                .'lagi — itu bentuk yang membuat formnya ikut tertimpa form buku.',
            );
        }
    }

    public function test_id_input_quick_add_tidak_bentrok(): void
    {
        $document = $this->parse($this->bookFormPage('books.edit'));

        $seen = [];

        foreach ($document->getElementsByTagName('input') as $input) {
            $id = $input->getAttribute('id');
            if ($id === '') {
                continue;
            }

            $this->assertArrayNotHasKey(
                $id,
                $seen,
                "Duplikat id \"{$id}\" di halaman edit buku. <label for> hanya "
                .'menunjuk ke elemen pertama dengan id itu, jadi klik label '
                .'tidak akan memfokuskan input yang dimaksud.',
            );

            $seen[$id] = true;
        }
    }

    public function test_tambah_kategori_lewat_quick_add_mengembalikan_json(): void
    {
        $this->actingAs($this->librarian())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('categories.quick'), ['name' => 'Sastra Puisi'])
            ->assertOk()
            ->assertJsonPath('name', 'Sastra Puisi');

        $this->assertDatabaseHas('categories', ['name' => 'Sastra Puisi']);
    }

    public function test_nama_duplikat_menghasilkan_422_yang_bisa_dibaca_javascript(): void
    {
        Category::factory()->create(['name' => 'Fiksi']);

        // 422 wajib: `initQuickAdd()` membaca `errors` dari body JSON untuk
        // menulis pesan di dalam modal. Kalau endpoint ini membalas 500 atau
        // HTML, user hanya melihat modal yang diam-diam tidak mengubah apa pun.
        $this->actingAs($this->librarian())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('categories.quick'), ['name' => 'Fiksi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_anggota_tidak_bisa_memakai_quick_add(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_ANGGOTA]);

        $this->actingAs($member)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('categories.quick'), ['name' => 'Fiksi'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Fiksi']);
    }
}
