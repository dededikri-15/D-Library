<?php

namespace Tests\Feature;

use App\Actions\NotifyWaitingList;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Models\WaitingList;
use App\Notifications\ActionLogged;
use App\Notifications\BookAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WaitingListTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------
    // Bergabung ke antrean
    // -------------------------------------------------------------

    public function test_anggota_bisa_mengantre_buku_yang_sedang_dipinjam(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'notified_at' => null,
        ]);
    }

    public function test_klik_ganda_tidak_membuat_antrean_ganda(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();

        $this->actingAs($member)->post(route('waiting-lists.store', $book));
        $this->actingAs($member)->post(route('waiting-lists.store', $book));

        $this->assertSame(1, WaitingList::where('user_id', $member->id)
            ->where('book_id', $book->id)
            ->count());
    }

    public function test_masuk_antrean_menghasilkan_jejak_aksi_untuk_pelakunya(): void
    {
        Notification::fake();

        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();

        $this->actingAs($member)->post(route('waiting-lists.store', $book));

        Notification::assertSentTo(
            $member,
            ActionLogged::class,
            fn (ActionLogged $notification) => $notification->type === 'waiting_list_joined',
        );
    }

    public function test_tidak_bisa_mengantre_buku_yang_masih_tersedia(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->available()->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', $book))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_tidak_bisa_mengantre_buku_nonaktif(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->inactive()->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', $book))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_tidak_bisa_mengantre_buku_yang_sedang_dipinjam_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        Loan::factory()->for($member, 'user')->for($book, 'book')->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', $book))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_batas_antrean_per_anggota_diterapkan(): void
    {
        config(['perpustakaan.waiting_list.max_per_user' => 2]);

        $member = User::factory()->anggota()->create();
        $waiting = Book::factory()->borrowed()->count(2)->create();

        foreach ($waiting as $book) {
            WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);
        }

        $extra = Book::factory()->borrowed()->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', $extra))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $extra->id,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $book = Book::factory()->borrowed()->create();

        $this->post(route('waiting-lists.store', $book))
            ->assertRedirect(route('login'));
    }

    public function test_staff_tidak_bisa_mengantre(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->borrowed()->create();

        $this->actingAs($staff)
            ->post(route('waiting-lists.store', $book))
            ->assertForbidden();
    }

    public function test_id_buku_non_numerik_menghasilkan_404(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->post(route('waiting-lists.store', 'abc'))
            ->assertNotFound();
    }

    // -------------------------------------------------------------
    // Membatalkan antrean
    // -------------------------------------------------------------

    public function test_anggota_bisa_membatalkan_antrean_sendiri(): void
    {
        Notification::fake();

        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->delete(route('waiting-lists.destroy', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);

        Notification::assertSentTo(
            $member,
            ActionLogged::class,
            fn (ActionLogged $notification) => $notification->type === 'waiting_list_left',
        );
    }

    public function test_membatalkan_antrean_tidak_menghapus_entri_orang_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $other->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->delete(route('waiting-lists.destroy', $book))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('waiting_lists', [
            'user_id' => $other->id,
            'book_id' => $book->id,
        ]);
    }

    // -------------------------------------------------------------
    // Halaman antrean saya
    // -------------------------------------------------------------

    public function test_halaman_antrean_menampilkan_buku_yang_diangtre(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('waiting-lists.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_halaman_antrean_kosong_untuk_anggota_baru(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('waiting-lists.index'))
            ->assertOk()
            ->assertSee('Belum ada buku yang diantre');
    }

    public function test_anggota_hanya_melihat_antreannya_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $other->id, 'book_id' => $book->id]);

        $this->actingAs($member)
            ->get(route('waiting-lists.index'))
            ->assertOk()
            ->assertDontSee($book->title);
    }

    // -------------------------------------------------------------
    // Notifikasi ketersediaan
    // -------------------------------------------------------------

    public function test_pengembalian_mengabari_seluruh_yang_mengantre(): void
    {
        Notification::fake();

        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->borrowed()->create();
        $borrower = User::factory()->anggota()->create();
        $loan = Loan::factory()->for($borrower, 'user')->for($book, 'book')->create();

        $waiters = User::factory()->anggota()->count(2)->create();
        foreach ($waiters as $waiter) {
            WaitingList::create(['user_id' => $waiter->id, 'book_id' => $book->id]);
        }

        $bystander = User::factory()->anggota()->create();

        $this->actingAs($staff)
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        foreach ($waiters as $waiter) {
            Notification::assertSentTo($waiter, BookAvailable::class);
            $this->assertNotNull(
                WaitingList::where('user_id', $waiter->id)->first()?->notified_at
            );
        }

        Notification::assertNotSentTo($bystander, BookAvailable::class);
    }

    public function test_pengembalian_tidak_mengirim_notifikasi_dobel(): void
    {
        Notification::fake();

        $book = Book::factory()->borrowed()->create();
        $member = User::factory()->anggota()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        // Klaim pertama: buku kebetulan sudah available (staf baru saja
        // menambah eksemplar lewat jalur lain), aksi dikirim dua kali —
        // hanya baris yang belum diklaim yang menghasilkan notifikasi.
        $book->update(['status' => Book::STATUS_AVAILABLE]);

        $action = app(NotifyWaitingList::class);
        $first = $action->handle($book);
        $second = $action->handle($book);

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        Notification::assertSentToTimes($member, BookAvailable::class, 1);
    }

    public function test_tidak_mengabari_selama_buku_masih_dipinjam_orang_lain(): void
    {
        Notification::fake();

        // Buku punya dua eksemplar; satu sudah kembali tapi satu lagi masih
        // dipinjam — status buku tetap `borrowed`, jadi tidak ada kabar.
        $book = Book::factory()->borrowed()->create();
        $book->copies()->create(['status' => BookCopy::STATUS_BORROWED]);
        $member = User::factory()->anggota()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $this->assertSame(0, app(NotifyWaitingList::class)->handle($book));
        Notification::assertNotSentTo($member, BookAvailable::class);
    }

    public function test_pengembalian_lewat_jalur_hapus_peminjaman_juga_mengabari(): void
    {
        Notification::fake();

        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->borrowed()->create();
        $borrower = User::factory()->anggota()->create();
        $loan = Loan::factory()->for($borrower, 'user')->for($book, 'book')->create();

        $waiter = User::factory()->anggota()->create();
        WaitingList::create(['user_id' => $waiter->id, 'book_id' => $book->id]);

        $this->actingAs($staff)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect();

        Notification::assertSentTo($waiter, BookAvailable::class);
    }

    // -------------------------------------------------------------
    // Keluar otomatis saat meminjam + siklus ronde
    // -------------------------------------------------------------

    public function test_meminjam_buku_mengeluarkan_diri_dari_antrean(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        // Buku kembali tersedia (mis. lewat pengembalian jalur lain):
        // status buku DAN eksemplarnya ikut diperbarui, persis seperti
        // yang dilakukan completeReturn().
        $book->update(['status' => Book::STATUS_AVAILABLE]);
        $book->copies()->update(['status' => BookCopy::STATUS_AVAILABLE]);

        $this->actingAs($member)
            ->post(route('books.borrow', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('waiting_lists', [
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_pinjam_mereset_penanda_notifikasi_antrean_lain(): void
    {
        $member = User::factory()->anggota()->create();
        $other = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create([
            'user_id' => $other->id,
            'book_id' => $book->id,
            'notified_at' => now(),
        ]);

        $book->update(['status' => Book::STATUS_AVAILABLE]);
        $book->copies()->update(['status' => BookCopy::STATUS_AVAILABLE]);

        $this->actingAs($member)->post(route('books.borrow', $book));

        // Buku keluar dari rak lagi -> ronde berikutnya boleh mengabari
        // $other, jadi penandanya dikembalikan ke null.
        $this->assertNull(
            WaitingList::where('user_id', $other->id)->first()?->notified_at
        );
    }

    // -------------------------------------------------------------
    // Tampilan di halaman detail buku (Task 24.6)
    // -------------------------------------------------------------

    public function test_tombol_daftar_tunggu_tampil_ketika_semua_eksemplar_habis(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-waiting-toggle', $content);
        // Kedua endpoint harus ada di DOM karena satu form dipakai untuk
        // masuk DAN keluar antrean — sama seperti form favorit.
        $this->assertStringContainsString('data-store-url="'.route('waiting-lists.store', $book).'"', $content);
        $this->assertStringContainsString('data-destroy-url="'.route('waiting-lists.destroy', $book).'"', $content);
        $this->assertStringContainsString('Masuk daftar tunggu', $content);

        // Tombol pinjam tetap dirender tapi nonaktif; alasan matinya
        // terbaca lewat atribut title.
        $this->assertStringContainsString('cursor-not-allowed', $content);
    }

    public function test_tombol_daftar_tunggu_tidak_tampil_bila_buku_tersedia(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->available()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-waiting-toggle', $content);
        $this->assertStringNotContainsString('Masuk daftar tunggu', $content);
    }

    public function test_tombol_daftar_tunggu_tidak_tampil_untuk_buku_nonaktif(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->inactive()->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-waiting-toggle', $content);
    }

    public function test_tombol_daftar_tunggu_tidak_tampil_saat_buku_dipinjam_sendiri(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        Loan::factory()->for($member, 'user')->for($book, 'book')->create();

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-waiting-toggle', $content);
    }

    public function test_tombol_daftar_tunggu_tidak_tampil_untuk_staff(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->borrowed()->create();

        $content = $this->actingAs($staff)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        // Staff meminjam lewat /peminjaman, bukan tombol ini.
        $this->assertStringNotContainsString('data-waiting-toggle', $content);
    }

    public function test_anggota_yang_sudah_mengantre_melihat_tombol_keluar(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->borrowed()->create();
        WaitingList::create(['user_id' => $member->id, 'book_id' => $book->id]);

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        // Sudah mengantre: form harus menunjuk ke endpoint hapus sejak awal,
        // bukan menunggu satu request dulu.
        $this->assertStringContainsString('data-waiting-toggle', $content);
        $this->assertStringContainsString('aria-pressed="true"', $content);
        $this->assertStringContainsString('name="_method" value="DELETE"', $content);
        $this->assertStringContainsString('Keluar dari antrean', $content);
    }

    // -------------------------------------------------------------
    // Navigasi ke halaman antrean
    // -------------------------------------------------------------

    public function test_menu_anggota_menampilkan_antrean_saya(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('waiting-lists.index'), false)
            ->assertSee('Daftar Tunggu', false);
    }

    public function test_menu_staff_tidak_menampilkan_antrean(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('waiting-lists.index'), false)
            ->assertDontSee('Daftar Tunggu', false);
    }

    // -------------------------------------------------------------
    // Kedaluwarsa (command scheduler)
    // -------------------------------------------------------------

    public function test_command_menghapus_entri_yang_sudah_lewat_jendela(): void
    {
        $expired = WaitingList::factory()->notified()->create([
            'notified_at' => now()->subHours(25),
        ]);
        $fresh = WaitingList::factory()->notified()->create();
        $stillWaiting = WaitingList::factory()->create([
            'notified_at' => null,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('waiting-lists:expire')->assertSuccessful();

        $this->assertDatabaseMissing('waiting_lists', ['id' => $expired->id]);
        $this->assertDatabaseHas('waiting_lists', ['id' => $fresh->id]);
        // Yang belum pernah dikabari tidak pernah kedaluwarsa — tugasnya
        // memang menunggu selama buku belum tersedia.
        $this->assertDatabaseHas('waiting_lists', ['id' => $stillWaiting->id]);
    }

    public function test_jendela_kedaluwarsa_mengikuti_konfigurasi(): void
    {
        config(['perpustakaan.waiting_list.notify_window_hours' => 2]);

        $entry = WaitingList::factory()->notified()->create([
            'notified_at' => now()->subHours(3),
        ]);
        $withinWindow = WaitingList::factory()->notified()->create([
            'notified_at' => now()->subHour(),
        ]);

        $this->artisan('waiting-lists:expire')->assertSuccessful();

        $this->assertDatabaseMissing('waiting_lists', ['id' => $entry->id]);
        $this->assertDatabaseHas('waiting_lists', ['id' => $withinWindow->id]);
    }
}
