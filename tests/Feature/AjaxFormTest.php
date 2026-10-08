<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjaxFormTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->create(['role' => User::ROLE_ANGGOTA]);
    }

    /**
     * Permintaan seperti yang dikirim `window.ajax()`: header AJAX + mau JSON.
     */
    private function asAjax(): self
    {
        return $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);
    }

    public function test_tambah_favorit_lewat_ajax_mengembalikan_json_bukan_redirect(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->asAjax()
            ->actingAs($member)
            ->post(route('favorites.store', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku ditambahkan ke favorit.')
            ->assertJsonPath('is_favorite', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_hapus_favorit_lewat_ajax_mengembalikan_json_bukan_redirect(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->asAjax()
            ->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku dihapus dari favorit.')
            ->assertJsonPath('is_favorite', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    /*
     * Dua test di bawah adalah alasan inti Task 14.7. Tanpa keduanya, AJAX
     * bisa "berhasil" sementara form aslinya sudah mati — dan tidak ada siapa
     * pun yang sadar sampai ada user yang JS-nya mati atau formnya error.
     */

    public function test_tambah_favorit_tanpa_ajax_tetap_redirect_dengan_flash(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('favorites.store', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', 'Buku ditambahkan ke favorit.');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_hapus_favorit_tanpa_ajax_tetap_redirect_dengan_flash(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->delete(route('favorites.destroy', $book))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('status', 'Buku dihapus dari favorit.');
    }

    public function test_hapus_favorit_yang_sudah_tidak_ada_melapor_jujur(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        // Dua request hapus berurutan: yang kedua tidak menghapus apa pun.
        $this->asAjax()->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku dihapus dari favorit.');

        $this->asAjax()->actingAs($member)
            ->delete(route('favorites.destroy', $book))
            ->assertOk()
            ->assertJsonPath('message', 'Buku ini memang tidak ada di favorit.')
            ->assertJsonPath('is_favorite', false);
    }

    public function test_menambah_favorit_dua_kali_tetap_aman(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $this->asAjax()->actingAs($member)->post(route('favorites.store', $book))->assertOk();
        $this->asAjax()->actingAs($member)->post(route('favorites.store', $book))->assertOk();

        $this->assertSame(
            1,
            Favorite::query()->where('user_id', $member->id)->where('book_id', $book->id)->count(),
        );
    }

    public function test_aksi_favorit_masih_melarang_orang_lain(): void
    {
        $owner = $this->member();
        $other = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $owner->id, 'book_id' => $book->id]);

        // Controller sudah dibatasi lewat relasi `$user->favorites()`, jadi
        // favorit orang lain tidak boleh ikut terhapus meski id-nya benar.
        $this->asAjax()->actingAs($other)
            ->delete(route('favorites.destroy', $book))
            ->assertOk();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_tamu_tidak_bisa_menambah_favorit_lewat_ajax(): void
    {
        $book = Book::factory()->create();

        $this->asAjax()
            ->post(route('favorites.store', $book))
            ->assertUnauthorized();

        $this->assertDatabaseCount('favorites', 0);
    }

    /*
     * Bentuk HTML yang dikirim JS. Test ini yang menangkap "AJAX-nya jalan
     * tapi formnya salah", yang tidak akan terlihat dari respons JSON.
     */

    public function test_form_favorit_menyediakan_semua_data_yang_dibutuhkan_js(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-ajax', $content);
        $this->assertStringContainsString('data-favorite-toggle', $content);
        // Tombol dalam form toggle memakai atribut generik (`data-toggle-*`),
        // karena daftar tunggu memakai bentuk form yang persis sama.
        $this->assertStringContainsString('data-toggle-button', $content);
        $this->assertStringContainsString('data-toggle-label', $content);
        $this->assertStringContainsString('data-toggle-icon', $content);

        // Kedua endpoint harus ada di DOM, karena satu form dipakai untuk
        // tambah DAN hapus. Kalau `data-destroy-url` hilang, klik kedua
        // mengirim POST ke endpoint tambah — jadi buku tidak bisa dihapus
        // tanpa reload halaman.
        $this->assertStringContainsString('data-store-url="'.route('favorites.store', $book).'"', $content);
        $this->assertStringContainsString('data-destroy-url="'.route('favorites.destroy', $book).'"', $content);

        // Label dua arah juga harus ada di Blade, bukan di-hardcode di JS.
        $this->assertStringContainsString('data-label-add="Tambah ke favorit"', $content);
        $this->assertStringContainsString('data-label-remove="Hapus dari favorit"', $content);
    }

    public function test_form_favorit_yang_sudah_terisi_menunjuk_ke_endpoint_hapus(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        Favorite::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="true"', $content);
        $this->assertStringContainsString('name="_method" value="DELETE"', $content);
    }

    public function test_form_favorit_belum_terisi_menunjuk_ke_endpoint_tambah(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-pressed="false"', $content);
        $this->assertStringNotContainsString('name="_method" value="DELETE"', $content);
    }

    public function test_form_favorit_menanda_fallback_yang_aman(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        // `data-ajax-fallback` hanya boleh dipakai form yang aksinya idempoten.
        // Kalau atribut ini hilang, `initAjaxForms` tidak akan pernah mengirim
        // ulang request — dan itu memang pilihan yang benar untuk form yang
        // belum diaudit, tapi harus terlihat di sini supaya tidak dihapus
        // tanpa dipikirkan.
        $this->assertStringContainsString('data-ajax-fallback', $content);
    }

    public function test_form_favorit_tidak_ada_untuk_staff_dan_tamu(): void
    {
        $book = Book::factory()->create();

        // Pustakawan mengelola buku, bukan favorit miliknya sendiri.
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('data-favorite-toggle', false);

        // Tamu diarahkan ke login, jadi tidak ada form yang perlu ditampilkan.
        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('data-favorite-toggle', false);
    }

    public function test_aksi_luar_javascript_tidak_berubah_untuk_form_lain(): void
    {
        $member = $this->member();
        $book = Book::factory()->create();

        // Form peminjaman tetap jalur biasa: memuat ulang halaman memang
        // benar di sana karena status buku berubah di banyak tempat sekaligus.
        $this->actingAs($member)
            ->post(route('books.borrow', $book))
            ->assertRedirect();

        $this->actingAs($member)
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertDontSee('data-ajax', false);
    }
}
