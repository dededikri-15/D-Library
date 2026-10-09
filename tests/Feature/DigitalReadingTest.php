<?php

namespace Tests\Feature;

use App\Actions\RecordReading;
use App\Models\Book;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Task 11.1 sampai 11.8: pembaca digital, pembatasan akses, pencatatan
 * aktivitas, posisi halaman, dan "lanjut membaca".
 *
 * Test akses keamanan dasar sudah ada di SecurityTest dan BookFileUploadTest. Yang
 * di sini adalah perilaku yang baru ditambahkan: pencatatan kunjungan, lompat
 * halaman, penyimpanan posisi, dan tautan lanjut membaca.
 */
class DigitalReadingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buku digital yang sedang dipinjam anggota `$member`.
     */
    protected function borrowedBook(?User $member = null, int $pages = 320): Book
    {
        Storage::fake('local');

        $book = Book::factory()->create([
            'status' => Book::STATUS_BORROWED,
            'pages' => $pages,
        ]);

        UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf')
            ->storeAs('books', $book->id.'.pdf', 'local');

        // Path-nya harus sama dengan yang ada di kolom `file` — kalau tidak,
        // `books.file` menjawab 404 (berkasnya "hilang") dan jalur stream PDF
        // tidak pernah teruji oleh helper ini.
        $book->update(['file' => 'books/'.$book->id.'.pdf']);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => ($member ?? User::factory()->anggota()->create())->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        return $book;
    }

    /*
    |--------------------------------------------------------------------------
    | 11.1 / 11.2 - Halaman pembaca dan viewer
    |--------------------------------------------------------------------------
    */

    public function test_anggota_dengan_pinjaman_aktif_dapat_membuka_pembaca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee($book->title)
            // Viewer harus menunjuk ke route berkas yang diperiksa, bukan ke
            // path storage yang bisa ditebak.
            ->assertSee(route('books.file', $book).'#page=1', false);
    }

    public function test_pembaca_menampilkan_kontrol_halaman_dan_simpan_posisi(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('Buka halaman')
            ->assertSee('Simpan posisi')
            ->assertSee('dari 320 halaman')
            // Input harus punya batas atas sesuai jumlah halaman buku.
            ->assertSee('max="320"', false);
    }

    public function test_query_page_dipakai_untuk_membuka_halaman_tertentu(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 42]))
            ->assertOk()
            ->assertSee(route('books.file', $book).'#page=42', false);
    }

    /**
     * Task 11.9 — kontrol zoom harus ikut terkirim bersama viewport-nya.
     *
     * Yang diuji di sini hanya keberadaan kontrol (perilaku kliknya ada di
     * browser, tidak dicakup test PHP). Yang lebih penting justru di
     * SecurityTest: CSP harus `object-src 'self'`, kalau tidak `<object>`
     * PDF-nya diblokir dan halaman ini selalu menampilkan fallback.
     */
    public function test_pembaca_menampilkan_kontrol_zoom_dan_viewport(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 120);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('data-reader-viewport', false)
            ->assertSee('data-reader-zoom-controls', false)
            ->assertSee('data-reader-zoom-in', false)
            ->assertSee('data-reader-zoom-out', false)
            ->assertSee('data-reader-zoom-reset', false)
            ->assertSee(__('reader.zoom_in'))
            ->assertSee(__('reader.zoom_reset'));
    }

    /**
     * Task 11.10 — tutup/buka lagi PDF.
     *
     * Tombol "Tutup PDF" hidup di toolbar (yang tersembunyi sampai JS aktif),
     * panel "sudah ditutup" sudah dirender tersembunyi lengkap dengan templated
     * teks halamannya, dan tombol buka ulang menunggu di sana.
     */
    public function test_pembaca_menampilkan_kontrol_tutup_dan_buka_ulang(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 89);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('data-reader-close', false)
            ->assertSee('data-reader-open', false)
            ->assertSee('data-reader-closed', false)
            ->assertSee('data-page-template', false)
            ->assertSee(__('reader.close_pdf'))
            ->assertSee(__('reader.open_pdf_again'))
            ->assertSee(__('reader.pdf_closed'));
    }

    /*
    |--------------------------------------------------------------------------
    | 11.3 / 11.4 - Pembatasan akses
    |--------------------------------------------------------------------------
    */

    public function test_anggota_tanpa_pinjaman_tidak_bisa_membuka_pembaca(): void
    {
        Storage::fake('local');

        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE, 'pages' => 100]);
        UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf')
            ->storeAs('books', $book->id.'.pdf', 'local');
        $book->update(['file' => $book->id.'.pdf']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.read', $book))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $book = $this->borrowedBook();

        $this->get(route('books.read', $book))->assertRedirect(route('login'));
    }

    public function test_anggota_tidak_bisa_membaca_pdf_yang_bukan_pinjamannya(): void
    {
        $book = $this->borrowedBook();

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.file', $book))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | 11.11 - Tombol "Buka halaman" dan cache berkas PDF
    |--------------------------------------------------------------------------
    */

    public function test_tombol_buka_halaman_memuat_ulang_elemen_object(): void
    {
        // Regresi (laporan user): tombol "Buka halaman" tidak bergerak sama
        // sekali. Penyebabnya menimpa atribut `data` yang hanya berbeda pada
        // fragmen `#page=` — browser menganggapnya perubahan URL di dalam
        // dokumen yang sama, dan viewer PDF bawaan tidak meresponsnya.
        // Satu-satunya jalur pindah halaman yang pasti dihormati viewer adalah
        // elemen baru yang dimuat dari nol.
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString(
            'function reloadReaderTo(page)',
            $js,
            'Pindah halaman harus lewat reloadReaderTo() yang mengganti elemen <object>.',
        );
        $this->assertStringContainsString('current.replaceWith(fresh)', $js);
        $this->assertStringContainsString(
            'fresh.setAttribute(\'data\', `${fileUrl}#page=${page}`)',
            $js,
            'Elemen baru harus dibuka pada halaman tujuan lewat fragment #page=.',
        );

        // Tidak boleh ada jalur lama yang menyentuh atribut `data` langsung:
        // jalur itulah yang terdeteksi tidak menggerakkan viewer.
        $this->assertSame(
            1,
            substr_count($js, "setAttribute('data'"),
            'Hanya reloadReaderTo() yang boleh mengubah atribut data pembaca.',
        );
    }

    public function test_berkas_pdf_mengirim_header_cache_supaya_pindah_halaman_tidak_mengunduh_ulang(): void
    {
        // Tiap kali user menekan "Buka halaman", <object> dimuat ulang. Tanpa
        // header ini berkasnya diunduh penuh setiap kali dan kuota rate limit
        // 30 permintaan per menit cepat habis untuk satu sesi baca.
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $response = $this->actingAs($member)->get(route('books.file', $book));

        $response->assertOk();

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl, 'Berkas berbayar akses tidak boleh masuk cache bersama.');
        $this->assertStringContainsString('max-age=', $cacheControl);
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->assertNotEmpty($response->headers->get('Last-Modified'));
    }

    public function test_klien_yang_sudah_punya_salinan_dijawab_304_tanpa_isi(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $first = $this->actingAs($member)->get(route('books.file', $book));
        $first->assertOk();

        $etag = (string) $first->headers->get('ETag');

        $second = $this->actingAs($member)
            ->withHeaders(['If-None-Match' => $etag])
            ->get(route('books.file', $book));

        $second->assertStatus(304)->assertHeader('ETag', $etag);
        $this->assertEmpty($second->getContent(), 'Respons 304 tidak boleh mengulang isi PDF.');
    }

    public function test_if_modified_sesuai_waktu_file_juga_dijawab_304(): void
    {
        // Klien lama yang hanya mengirim If-Modified-Since (tanpa ETag)
        // tetap harus dijawab hemat, bukan diberi isi penuh lagi.
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $first = $this->actingAs($member)->get(route('books.file', $book));
        $first->assertOk();

        $this->actingAs($member)
            ->withHeaders(['If-Modified-Since' => (string) $first->headers->get('Last-Modified')])
            ->get(route('books.file', $book))
            ->assertStatus(304);
    }

    /*
    |--------------------------------------------------------------------------
    | 11.5 - Mencatat aktivitas membaca
    |--------------------------------------------------------------------------
    */

    public function test_staff_bisa_membaca_untuk_pratinjau_tanpa_membuat_riwayat(): void
    {
        $book = $this->borrowedBook();
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee(route('books.file', $book).'#page=1', false);

        // Pratinjau staff tidak masuk daftar riwayat baca: tidak ada halaman
        // yang menampilkan data itu, jadi hanya jadi sampah baris.
        $this->assertDatabaseMissing('reading_histories', [
            'user_id' => $staff->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_membuka_pembaca_mencatat_kunjungan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_membuka_pembaca_untuk_pertama_kali_tidak_gagal(): void
    {
        // Regresi: `last_page` punya default 0 di database, tapi objek
        // Eloquent yang baru dibuat tidak punya atribut itu sehingga
        // `clampPage(null)` menyebabkan TypeError -> HTTP 500.
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('#page=1', false);

        $this->assertSame(1, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }

    public function test_membuka_pembaca_lagi_tidak_menimpa_posisi_yang_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        app(RecordReading::class)->savePage($member, $book, 120);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        // Posisi harus tetap 120, bukan kembali ke 1.
        $this->assertSame(120, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }

    public function test_membuka_pembaca_memperbarui_waktu_baca_terakhir(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 50,
            'last_read_at' => now()->subWeek(),
        ]);

        $this->actingAs($member)->get(route('books.read', $book))->assertOk();

        $history = ReadingHistory::where('book_id', $book->id)->firstOrFail();
        $this->assertTrue($history->last_read_at->isAfter(now()->subDay()));
    }

    /*
    |--------------------------------------------------------------------------
    | 11.6 - Menyimpan halaman terakhir
    |--------------------------------------------------------------------------
    */

    public function test_anggota_bisa_menyimpan_posisi_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->from(route('books.read', $book))
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 88,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 88,
        ]);
    }

    public function test_halaman_tidak_boleh_melebihi_jumlah_halaman_buku(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 99999,
            ]);

        $this->assertSame(320, (int) ReadingHistory::where('book_id', $book->id)->value('last_page'));
    }

    public function test_query_page_dijepit_oleh_server(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 999999]))
            ->assertOk()
            ->assertSee('#page=320', false);
    }

    public function test_query_page_aneh_tidak_membuat_halaman_rusak(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        foreach (['abc', '-5', '0', '3.7'] as $value) {
            $this->actingAs($member)
                ->get(route('books.read', ['book' => $book, 'page' => $value]))
                ->assertOk()
                ->assertSee('#page=', false);
        }
    }

    public function test_anggota_tidak_bisa_menyimpan_posisi_buku_milik_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = $this->borrowedBook($other, 320);

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => $book->id,
                'last_page' => 10,
            ]);

        // Tidak boleh membuat baris di atas nama orang lain.
        $this->assertDatabaseMissing('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_menyimpan_posisi_untuk_buku_tidak_ada_ditolak(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->post(route('reading-histories.store'), [
                'book_id' => 999999,
                'last_page' => 5,
            ])
            ->assertSessionHasErrors('book_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 11.7 - Fitur lanjut membaca
    |--------------------------------------------------------------------------
    */

    public function test_riwayat_baca_menampilkan_tautan_lanjut_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 77);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('Lanjut baca')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 77]), false);
    }

    public function test_riwayat_baca_tidak_menampilkan_lanjut_baca_untuk_buku_tanpa_file(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['file' => null, 'pages' => 100]);

        app(RecordReading::class)->savePage($member, $book, 20);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertDontSee('Lanjut baca');
    }

    public function test_detail_buku_menampilkan_tombol_lanjut_dari_halaman_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 55);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Lanjut dari halaman 55')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 55]), false);
    }

    public function test_detail_buku_menampilkan_baca_buku_biasa_bila_belum_pernah_membaca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Baca Buku')
            ->assertDontSee('Lanjut dari halaman');
    }

    public function test_dasbor_anggota_menampilkan_lanjut_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 64);

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Lanjut baca')
            ->assertSee(route('books.read', ['book' => $book, 'page' => 64]), false);
    }

    public function test_membuka_pembaca_melanjutkan_dari_halaman_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 123);

        // Tanpa `?page=`, halaman baca harus memakai posisi tersimpan.
        $this->actingAs($member)
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('#page=123', false);
    }

    public function test_halaman_yang_ada_di_url_mengalahkan_posisi_tersimpan(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        app(RecordReading::class)->savePage($member, $book, 123);

        $this->actingAs($member)
            ->get(route('books.read', ['book' => $book, 'page' => 10]))
            ->assertOk()
            ->assertSee('#page=10', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Aturan internal RecordReading
    |--------------------------------------------------------------------------
    */

    public function test_clamp_menolak_halaman_kurang_dari_satu(): void
    {
        $action = app(RecordReading::class);
        $book = Book::factory()->create(['pages' => 100]);

        $this->assertSame(1, $action->clampPage(0, $book));
        $this->assertSame(1, $action->clampPage(-10, $book));
    }

    public function test_clamp_tidak_membatasi_buku_tanpa_jumlah_halaman(): void
    {
        $action = app(RecordReading::class);
        $book = Book::factory()->create(['pages' => 0]);

        $this->assertSame(999, $action->clampPage(999, $book));
    }

    public function test_visit_tidak_membuat_baris_ganda(): void
    {
        $action = app(RecordReading::class);
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();

        $action->visit($member, $book);
        $action->visit($member, $book);
        $action->savePage($member, $book, 9);

        $this->assertSame(1, ReadingHistory::where('user_id', $member->id)->where('book_id', $book->id)->count());
    }
}
