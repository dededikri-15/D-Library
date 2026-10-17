<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ProvidesBorrowedBook;
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
    use ProvidesBorrowedBook;
    use RefreshDatabase;

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
}
