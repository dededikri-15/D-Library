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

class ReadingHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function borrowedBook(?User $member = null, int $pages = 320): Book
    {
        Storage::fake('local');

        $book = Book::factory()->create([
            'status' => Book::STATUS_BORROWED,
            'pages' => $pages,
        ]);

        UploadedFile::fake()->create('buku.pdf', 100, 'application/pdf')
            ->storeAs('books', $book->id.'.pdf', 'local');

        $book->update(['file' => $book->id.'.pdf']);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => ($member ?? User::factory()->anggota()->create())->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        return $book;
    }

    public function test_riwayat_baca_menampilkan_buku_terakhir_dibaca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        app(RecordReading::class)->savePage($member, $book, 42);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_riwayat_baca_menampilkan_halaman_terakhir(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        app(RecordReading::class)->savePage($member, $book, 42);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('42');
    }

    public function test_riwayat_baca_menampilkan_tanggal_terakhir_dibaca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        app(RecordReading::class)->savePage($member, $book, 10);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('Terakhir dibaca');
    }

    public function test_riwayat_baca_kosong_untuk_anggota_baru(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('Belum ada riwayat membaca');
    }

    public function test_anggota_hanya_melihat_riwayatnya_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = $this->borrowedBook($other);

        app(RecordReading::class)->savePage($other, $book, 10);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertDontSee($book->title);
    }

    public function test_anggota_bisa_menghapus_riwayat_baca(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member);

        $history = app(RecordReading::class)->savePage($member, $book, 10);

        $this->actingAs($member)
            ->delete(route('reading-histories.destroy', $history))
            ->assertRedirect();

        $this->assertDatabaseMissing('reading_histories', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_anggota_tidak_bisa_menghapus_riwayat_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = $this->borrowedBook($other);

        $history = app(RecordReading::class)->savePage($other, $book, 10);

        $this->actingAs($member)
            ->delete(route('reading-histories.destroy', $history))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('reading-histories.index'))->assertRedirect(route('login'));
    }

    public function test_staff_tidak_bisa_melihat_riwayat_baca(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('reading-histories.index'))
            ->assertForbidden();
    }

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

    public function test_riwayat_baca_tidak_menampilkan_lanjut_baca_untuk_halaman_nol(): void
    {
        $member = User::factory()->anggota()->create();
        $book = $this->borrowedBook($member, 320);

        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 0,
            'last_read_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertDontSee('Lanjut baca');
    }

    public function test_id_riwayat_non_numerik_menghasilkan_404(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->delete(route('reading-histories.destroy', 'abc'))
            ->assertNotFound();
    }
}
