<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProvidesBorrowedBook;
use Tests\TestCase;

class DigitalReadingCacheTest extends TestCase
{
    use ProvidesBorrowedBook;
    use RefreshDatabase;

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
}
