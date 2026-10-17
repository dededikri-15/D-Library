<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingGuardTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

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
}
