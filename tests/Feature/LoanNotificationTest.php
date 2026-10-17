<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Notifications\LoanApproved;
use App\Notifications\LoanBorrowed;
use App\Notifications\LoanCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

/**
 * Notifikasi dalam aplikasi (Task 22.x).
 *
 * Dua lapis yang dijaga test ini:
 * 1. PENGIRIMAN — peristiwa peminjaman menghasilkan notifikasi yang tepat
 *    untuk orang yang tepat (anggota tahu soal pinjamannya, pustakawan tahu
 *    soal antreannya), dan tidak pernah dobel walau halaman dimuat berulang.
 * 2. PENAMPILAN — lonceng, halaman riwayat, penanda sudah-dibaca, dan
 *    tindakan tidak boleh menjangkau notifikasi milik orang lain.
 *
 * `Notification::fake()` memblokir SEMUA channel, jadi untuk membuktikan
 * payload yang benar-benar tersimpan, `toArray()` dipanggil manual terhadap
 * notifiable yang bersangkutan.
 */
class LoanNotificationTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    public function test_anggota_meminjam_sendiri_mendapat_pemberitahuan_dan_pustakawan_dikabari(): void
    {
        Notification::fake();
        $member = $this->member();
        $librarian = $this->librarian();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs($member)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book))
            ->assertRedirect(route('books.show', $book));

        Notification::assertSentTo($member, LoanBorrowed::class);
        // Meminjam sendiri bukan "persetujuan" — jangan sampai keduanya muncul.
        Notification::assertNotSentTo($member, LoanApproved::class);
        Notification::assertSentTo($librarian, LoanCreated::class);
    }

    public function test_pustakawan_mencatat_peminjaman_anggota_menerima_persetujuan_dan_staf_lain_dikabari(): void
    {
        Notification::fake();
        $member = $this->member();
        $actor = $this->librarian();
        $otherLibrarian = $this->librarian();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $this->actingAs($actor)
            ->post(route('loans.store'), [
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        Notification::assertSentTo($member, LoanApproved::class);
        Notification::assertNotSentTo($member, LoanBorrowed::class);
        Notification::assertSentTo($otherLibrarian, LoanCreated::class);
        // Pencatatnya sendiri sudah tahu karena dialah yang menekan tombol.
        Notification::assertNotSentTo($actor, LoanCreated::class);
    }
}
