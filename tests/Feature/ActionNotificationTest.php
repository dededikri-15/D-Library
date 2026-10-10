<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Jejak aksi (ActionLogged): setiap aksi perubahan data menghasilkan satu
 * notifikasi untuk PELAKUNYA sendiri — bukan untuk orang lain.
 *
 * Dua hal yang dijaga test ini:
 * 1. PENGIRIMAN — aksi yang benar-benar mengubah data mengirim notifikasi
 *    dengan kode tipe yang tepat; aksi idempoten (klik favorit kedua kali,
 *    simpan form tanpa perubahan) TIDAK mengirim notifikasi ganda.
 * 2. PENAMPILAN — tipe baru punya judul di lang dan bisa diklik ke URL
 *    tujuannya.
 *
 * `Notification::fake()` memblokir SEMUA channel, jadi untuk membuktikan
 * payload yang tersimpan, `toArray()` dipanggil manual.
 */
class ActionNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->anggota()->create();
    }

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    /**
     * Pastikan user menerima ActionLogged dengan kode tipe tertentu.
     */
    private function assertActionLogged(User $user, string $type): void
    {
        $matched = Notification::sent($user, ActionLogged::class)
            ->contains(fn (ActionLogged $notification) => $notification->type === $type);

        $this->assertTrue(
            $matched,
            "Tidak menemukan notifikasi ActionLogged bertipe '{$type}' untuk user #{$user->id}."
        );
    }

    private function assertNoActionLogged(User $user, string $type): void
    {
        $matched = Notification::sent($user, ActionLogged::class)
            ->contains(fn (ActionLogged $notification) => $notification->type === $type);

        $this->assertFalse(
            $matched,
            "Tidak seharusnya ada notifikasi ActionLogged bertipe '{$type}' untuk user #{$user->id}."
        );
    }

    /* ------------------------------------------------------------------
     | Profil
     * ----------------------------------------------------------------- */

    public function test_mengubah_data_profil_memberi_notifikasi_jejak_aksi(): void
    {
        Notification::fake();
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'gender' => $user->gender,
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'profile_updated');
    }

    public function test_mengganti_kata_sandi_memberi_notifikasi_terpisah(): void
    {
        Notification::fake();
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'password' => 'rahasia-baru-456',
            'password_confirmation' => 'rahasia-baru-456',
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'password_changed');
        // Nama tidak berubah — jangan ikut dikabari "profil diperbarui".
        $this->assertNoActionLogged($user, 'profile_updated');
    }

    public function test_mengunggah_avatar_memberi_notifikasi_avatar_replaced(): void
    {
        Notification::fake();
        Storage::fake(User::avatarDisk());
        $user = $this->member();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender,
            'avatar' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ])->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'avatar_replaced');
    }

    public function test_menghapus_avatar_lewat_tombol_memberi_notifikasi(): void
    {
        Notification::fake();
        $user = $this->member();
        $user->update(['avatar' => 'avatars/foto-contoh.jpg']);

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.show'));

        $this->assertActionLogged($user, 'avatar_removed');
    }

    /* ------------------------------------------------------------------
     | Buku
     * ----------------------------------------------------------------- */

    public function test_pustakawan_menambah_buku_mendapat_notifikasi_book_created(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->make();

        $this->actingAs($staff)->post(route('books.store'), [
            'title' => $book->title,
            'isbn' => '978-602-111-201-9',
            'publication_year' => 2024,
            'status' => Book::STATUS_AVAILABLE,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'book_created');
    }

    public function test_mengubah_buku_memberi_notifikasi_book_updated_dengan_judul(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create();

        $this->actingAs($staff)->patch(route('books.update', $book), [
            'title' => 'Judul Baru Hasil Edit',
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'book_updated');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame('Judul Baru Hasil Edit', $payload['subject']);
    }

    public function test_menyimpan_buku_tanpa_perubahan_tidak_mengirim_notifikasi(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create();

        $this->actingAs($staff)->patch(route('books.update', $book), [
            'title' => $book->title,
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
        ])->assertRedirect();

        $this->assertNoActionLogged($staff, 'book_updated');
    }

    public function test_menghapus_buku_memberi_notifikasi_book_deleted(): void
    {
        Notification::fake();
        $staff = $this->librarian();
        $book = Book::factory()->create(['title' => 'Buku Akan Dihapus']);

        $this->actingAs($staff)->delete(route('books.destroy', $book))->assertRedirect();

        $this->assertActionLogged($staff, 'book_deleted');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame('Buku Akan Dihapus', $payload['subject']);
    }

    /* ------------------------------------------------------------------
     | Master data: kategori, penulis, penerbit
     * ----------------------------------------------------------------- */

    public function test_kategori_baru_update_dan_hapus_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('categories.store'), ['name' => 'Sains'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_created');

        $category = Category::where('name', 'Sains')->firstOrFail();

        $this->actingAs($staff)->patch(route('categories.update', $category), ['name' => 'Sains Populer'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_updated');

        $this->actingAs($staff)->delete(route('categories.destroy', $category))
            ->assertRedirect();
        $this->assertActionLogged($staff, 'category_deleted');
    }

    public function test_quick_add_kategori_juga_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->postJson(route('categories.quick'), ['name' => 'Novel'])
            ->assertOk();

        $this->assertActionLogged($staff, 'category_created');
    }

    public function test_penulis_dan_penerbit_baru_mengirim_jejak_aksi(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('authors.store'), ['name' => 'Penulis Uji'])
            ->assertRedirect();
        $this->assertActionLogged($staff, 'author_created');

        $this->actingAs($staff)->postJson(route('publishers.quick'), ['name' => 'Penerbit Uji'])
            ->assertOk();
        $this->assertActionLogged($staff, 'publisher_created');

        $author = Author::where('name', 'Penulis Uji')->firstOrFail();
        $this->actingAs($staff)->delete(route('authors.destroy', $author))->assertRedirect();
        $this->assertActionLogged($staff, 'author_deleted');

        $publisher = Publisher::where('name', 'Penerbit Uji')->firstOrFail();
        $this->actingAs($staff)->delete(route('publishers.destroy', $publisher))->assertRedirect();
        $this->assertActionLogged($staff, 'publisher_deleted');
    }

    /* ------------------------------------------------------------------
     | Pengguna
     * ----------------------------------------------------------------- */

    public function test_kelola_pengguna_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $staff = $this->librarian();

        $this->actingAs($staff)->post(route('users.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota-baru@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia-12345',
            'password_confirmation' => 'rahasia-12345',
            'role' => User::ROLE_ANGGOTA,
        ])->assertRedirect();
        $this->assertActionLogged($staff, 'user_created');

        $target = User::where('email', 'anggota-baru@perpustakaan.test')->firstOrFail();

        $this->actingAs($staff)->patch(route('users.update', $target), [
            'name' => 'Anggota Diganti',
            'email' => $target->email,
            'gender' => $target->gender,
            'password' => '',
            'role' => User::ROLE_ANGGOTA,
        ])->assertRedirect();
        $this->assertActionLogged($staff, 'user_updated');

        $this->actingAs($staff)->delete(route('users.destroy', $target))->assertRedirect();
        $this->assertActionLogged($staff, 'user_deleted');

        // Yang dikabari pelaku (pustakawan), bukan akun yang diedit.
        $this->assertNoActionLogged($target, 'user_updated');
    }

    /* ------------------------------------------------------------------
     | Favorit & riwayat baca (anggota)
     * ----------------------------------------------------------------- */

    public function test_favorit_ditambah_dan_dihapus_mengirim_jejak_aksi_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $book = Book::factory()->create();

        $this->actingAs($member)->post(route('favorites.store', $book))->assertRedirect();
        $this->assertActionLogged($member, 'favorite_added');

        // Klik kedua: idempoten — tidak ada notifikasi ganda.
        $this->actingAs($member)->post(route('favorites.store', $book))->assertRedirect();
        $this->assertSame(
            1,
            Notification::sent($member, ActionLogged::class)
                ->filter(fn (ActionLogged $n) => $n->type === 'favorite_added')
                ->count()
        );

        $this->actingAs($member)->delete(route('favorites.destroy', $book))->assertRedirect();
        $this->assertActionLogged($member, 'favorite_removed');

        // Hapus kedua kali: baris sudah tidak ada — jejaknya tidak dikarang.
        $this->actingAs($member)->delete(route('favorites.destroy', $book))->assertRedirect();
        $this->assertSame(
            1,
            Notification::sent($member, ActionLogged::class)
                ->filter(fn (ActionLogged $n) => $n->type === 'favorite_removed')
                ->count()
        );
    }

    public function test_simpan_posisi_baca_mengirim_jejak_aksi_dan_hapus_riwayat_juga(): void
    {
        Notification::fake();
        $member = $this->member();
        $book = Book::factory()->create([
            'status' => Book::STATUS_BORROWED,
            'file' => 'books/uji.pdf',
        ]);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)->post(route('reading-histories.store'), [
            'book_id' => $book->id,
            'last_page' => 12,
        ])->assertRedirect();
        $this->assertActionLogged($member, 'reading_saved');

        $history = ReadingHistory::where('user_id', $member->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->actingAs($member)->delete(route('reading-histories.destroy', $history))
            ->assertRedirect();
        $this->assertActionLogged($member, 'reading_removed');
    }

    /* ------------------------------------------------------------------
     | Peminjaman: celah yang belum punya kabar untuk pelaku
     * ----------------------------------------------------------------- */

    public function test_pustakawan_mencatat_peminjaman_mendapat_jejak_aksi(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs($staff)->post(route('loans.store'), [
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertActionLogged($staff, 'loan_recorded');

        $payload = Notification::sent($staff, ActionLogged::class)->last()->toArray($staff);
        $this->assertSame($book->title, $payload['subject']);
        $this->assertSame($member->name, $payload['user_name']);
    }

    public function test_perpanjangan_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->post(route('loans.mine.renew', $loan))
            ->assertRedirect();

        $this->assertActionLogged($member, 'loan_renewed');
    }

    public function test_pengajuan_pengembalian_mengirim_jejak_aksi_ke_pelaku_anggota(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        $this->assertActionLogged($member, 'return_requested_self');
    }

    public function test_pengembalian_dan_pembayaran_denda_mengirim_jejak_aksi_ke_pustakawan(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($staff)->post(route('loans.return', $loan))->assertRedirect();
        $this->assertActionLogged($staff, 'return_recorded');

        // Denda di-snapshot pengembalian. Kalau nol, buat denda berbayar
        // dulu supaya tombol "denda lunas" punya target.
        $loan->refresh();
        if ((int) $loan->fine === 0) {
            $loan->update(['fine' => 5000]);
        }

        $this->actingAs($staff)->post(route('loans.fine.pay', $loan))->assertRedirect();
        $this->assertActionLogged($staff, 'fine_paid');
    }

    public function test_menghapus_catatan_peminjaman_mengirim_jejak_aksi_ke_pelaku(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($staff)->delete(route('loans.destroy', $loan))->assertRedirect();

        $this->assertActionLogged($staff, 'loan_record_deleted');
    }

    /* ------------------------------------------------------------------
     | Penampilan: judul dari lang + klik ke URL tujuan
     * ----------------------------------------------------------------- */

    public function test_halaman_notifikasi_menampilkan_judul_tipe_aksi_dari_lang(): void
    {
        $member = $this->member();

        // Tanpa Notification::fake(): baris benar-benar ditulis ke database
        // supaya halaman riwayat bisa merendernya.
        $member->notify(new ActionLogged(
            'book_created',
            ['subject' => 'Buku Judul Tes'],
            route('books.index'),
        ));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Buku ditambahkan')
            ->assertSee('Buku Judul Tes');
    }

    public function test_membuka_notifikasi_jejak_aksi_mengarahkan_ke_url_payload(): void
    {
        $member = $this->member();

        $member->notify(new ActionLogged(
            'category_created',
            ['subject' => 'Kategori Tes'],
            route('categories.public'),
        ));

        $notification = $member->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('categories.public'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_payload_action_logged_membawa_tipe_param_dan_url(): void
    {
        $member = $this->member();

        $notification = new ActionLogged(
            'favorite_added',
            ['subject' => 'Buku Favorit'],
            route('favorites.index'),
        );

        $data = $notification->toArray($member);

        $this->assertSame('favorite_added', $data['type']);
        $this->assertSame('Buku Favorit', $data['subject']);
        $this->assertSame(route('favorites.index'), $data['url']);
    }
}
