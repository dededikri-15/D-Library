<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanApproved;
use App\Notifications\LoanBorrowed;
use App\Notifications\LoanCreated;
use App\Notifications\LoanDueSoon;
use App\Notifications\LoanOverdue;
use App\Notifications\LoanRejected;
use App\Notifications\LoanReturned;
use App\Notifications\LoanReturnRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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
    use RefreshDatabase;

    private function member(): User
    {
        return User::factory()->anggota()->create();
    }

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    private function loanFor(User $member, array $attributes = []): Loan
    {
        return Loan::factory()->create($attributes + [
            'user_id' => $member->id,
            'book_id' => Book::factory()->create(['status' => Book::STATUS_BORROWED]),
            'status' => Loan::STATUS_BORROWED,
        ]);
    }

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

    public function test_pengajuan_pengembalian_hanya_memberi_tahu_pustakawan_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $librarian = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        Notification::assertSentTo($librarian, LoanReturnRequested::class);
        Notification::assertNotSentTo($member, LoanReturnRequested::class);

        // Pengajuan kedua masih "pending" — kabarnya sudah pernah dikirim.
        $this->actingAs($member)
            ->post(route('loans.mine.request-return', $loan))
            ->assertRedirect();

        Notification::assertSentToTimes($librarian, LoanReturnRequested::class, 1);
    }

    public function test_pengembalian_oleh_pustakawan_mengabari_anggota_dengan_tanggal(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($staff)
            ->post(route('loans.return', $loan))
            ->assertRedirect();

        Notification::assertSentTo($member, LoanReturned::class);

        $data = Notification::sent($member, LoanReturned::class)->first()->toArray($member);

        $this->assertSame('returned', $data['type']);
        $this->assertNotEmpty($data['returned_at'], 'Payload harus membawa tanggal pengembalian yang sudah di-refresh.');
    }

    public function test_penghapusan_peminjaman_mengabari_anggota_dengan_alasan_kode(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member);

        $this->actingAs($staff)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect();

        Notification::assertSentTo(
            $member,
            LoanRejected::class,
            fn (LoanRejected $notification) => $notification->reason === LoanRejected::REASON_ADMIN_DELETED,
        );

        $data = Notification::sent($member, LoanRejected::class)->first()->toArray($member);

        // Kode, bukan kalimat — teksnya baru diterjemahkan saat dibaca.
        $this->assertSame('admin_deleted', $data['reason']);
    }

    public function test_keterlambatan_diberitahukan_ke_anggota_dan_pustakawan_tanpa_dobel(): void
    {
        Notification::fake();
        $member = $this->member();
        $staff = $this->librarian();
        $loan = $this->loanFor($member, [
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
        ]);

        // Dua kali buka beranda = dua kali pengecekan keterlambatan.
        $this->get(route('home'))->assertOk();
        $this->get(route('home'))->assertOk();

        Notification::assertSentToTimes($member, LoanOverdue::class, 1);
        Notification::assertSentToTimes($staff, LoanOverdue::class, 1);
        $this->assertNotNull($loan->fresh()->overdue_alerted_at);

        $memberData = Notification::sent($member, LoanOverdue::class)->first()->toArray($member);
        $staffData = Notification::sent($staff, LoanOverdue::class)->first()->toArray($staff);

        $this->assertSame('overdue_member', $memberData['type']);
        $this->assertArrayNotHasKey('user_name', $memberData, 'Anggota tidak perlu diberi tahu namanya sendiri.');

        $this->assertSame('overdue_staff', $staffData['type']);
        $this->assertSame($member->name, $staffData['user_name']);
        $this->assertNotEmpty($staffData['borrowed_at']);
        $this->assertNotEmpty($staffData['due_at']);
        $this->assertGreaterThan(0, $staffData['overdue_days']);
    }

    public function test_pengingat_mendekati_jatuh_tempo_terkirim_tepat_satu_kali(): void
    {
        Notification::fake();
        $member = $this->member();
        $loan = $this->loanFor($member, ['due_at' => now()->addDay()]);

        $this->artisan('loans:remind')->assertSuccessful();
        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentToTimes($member, LoanDueSoon::class, 1);

        $data = Notification::sent($member, LoanDueSoon::class)->first()->toArray($member);
        $this->assertSame('due_soon', $data['type']);
        $this->assertArrayHasKey('renewals_left', $data);
    }

    public function test_halaman_notifikasi_menampilkan_daftar_dan_jumlah_belum_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.types.due_soon.title'))
            ->assertSee(trans_choice('notifications.unread_count', 1));
    }

    /**
     * Regresi plural (Task 26.4): `trans_choice` harus dikirim KUNCI, bukan
     * hasil `__()`. Kirim hasil `__()` → `Translator::localeForChoice()` tidak
     * mengenali kiriman itu sebagai kunci → locale jatuh ke `fallback_locale`
     * ('id') → aturan jamak Indonesia selalu memilih bentuk pertama, jadi
     * layar Inggris tampil "2 unread notification".
     *
     * Dua teks dicek terpisah karena keduanya memakai kunci berbeda:
     * lonceng memakai `bell_unread`, halaman memakai `unread_count`.
     */
    public function test_lonceng_dan_halaman_notifikasi_memakai_bentuk_jamak_bahasa_inggris(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $html = $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-notification-unread-text[^>]*>.*?2 unread notifications\b/su',
            $html,
        );

        $this->assertMatchesRegularExpression(
            '/text-tertiary uppercase">\s*2 unread notifications\s*<\/p>/su',
            $html,
        );
    }

    /**
     * Pasangan angka 1: bentuk tunggal harus tetap Inggris dan benar —
     * tanpa "s". Pola lama ikut merusak ini karena teksnya jatuh ke locale
     * 'id' (walau hurufnya kebetulan sama untuk kunci tanpa "|" di id).
     */
    public function test_lonceng_memakai_bentuk_tunggal_bahasa_inggris(): void
    {
        $member = $this->member();
        $member->notify(new LoanDueSoon($this->loanFor($member)));

        $html = $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/data-notification-unread-text[^>]*>.*?1 unread notification\b/su',
            $html,
        );
    }

    public function test_menandai_satu_notifikasi_membuka_tujuannya_lalu_menenandai_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $stored = $member->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $stored))
            ->assertRedirect(route('loans.mine'));

        $this->assertNotNull($stored->fresh()->read_at);
    }

    public function test_notifikasi_orang_lain_tidak_bisa_ditandai_bahkan_dengan_id_asli(): void
    {
        $member = $this->member();
        $other = $this->member();
        $other->notify(new LoanDueSoon($this->loanFor($other)));
        $foreign = $other->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $foreign))
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_id_notifikasi_bukan_uuid_menghasilkan_404_bukan_500(): void
    {
        // Kolom id bertipe uuid. Tanpa `whereUuid` pada route, input semacam
        // ini masuk ke query dan di PostgreSQL melempar QueryException -> 500.
        $this->actingAs($this->member())
            ->get('/notifikasi/abc/tandai')
            ->assertNotFound();
    }

    public function test_tandai_semua_sudah_dibaca_menandai_seluruh_baris_sekaligus(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->post(route('notifications.readAll'))
            ->assertRedirect()
            ->assertSessionHas('status', __('notifications.mark_all_done'));

        $this->assertSame(0, $member->notifications()->whereNull('read_at')->count());
    }

    public function test_tamu_didakhalangi_melihat_notifikasi(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.readAll'))->assertRedirect(route('login'));
    }

    public function test_jenis_notifikasi_tidak_dikenal_tetap_tampil_dan_tidak_mengarah_ke_luar(): void
    {
        $member = $this->member();

        // Baris lama / payload asing: type tak dikenal dan `url` bukan milik
        // aplikasi ini. Keduanya tidak boleh membuat halaman rusak maupun
        // mengarahkan user ke domain lain.
        $stored = $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PayloadAsing',
            'data' => ['type' => 'tidak_dikenal', 'book_title' => 'Judul Misterius', 'url' => 'https://contoh-asing.test/x'],
            'created_at' => now(),
        ]);

        // Payload tanpa judul sama sekali: jatuh ke judul generik.
        $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PayloadKosong',
            'data' => ['type' => 'juga_tidak_dikenal'],
            'created_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Judul Misterius')
            ->assertSee(__('notifications.generic_title'), false);

        $this->actingAs($member)
            ->get(route('notifications.read', $stored))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_lonceng_tampil_di_topbar_untuk_user_masuk(): void
    {
        $this->actingAs($this->member())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('notifications.index'), false);
    }

    public function test_membuka_lonceng_menandai_semua_sudah_dibaca_lewat_ajax(): void
    {
        // Inilah yang dipanggil `initNotificationBell()` saat panel lonceng
        // dibuka: POST + Accept JSON, bukan form biasa, supaya angkanya bisa
        // dihapus di tempat tanpa memuat ulang halaman (yang akan menutup
        // panel yang baru saja dibuka).
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->postJson(route('notifications.readAll'))
            ->assertOk()
            ->assertJson(['message' => __('notifications.mark_all_done')]);

        $this->assertSame(0, $member->notifications()->whereNull('read_at')->count());
    }

    public function test_angka_dan_penanda_belum_dibaca_hilang_setelah_dibaca(): void
    {
        $member = $this->member();
        $loan = $this->loanFor($member);
        $member->notify(new LoanDueSoon($loan));

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-notification-badge', false)
            ->assertSee('<li data-notification-unread', false);

        $member->notifications()->update(['read_at' => now()]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-notification-badge', false)
            ->assertDontSee('<li data-notification-unread', false);
    }

    public function test_lonceng_tidak_tampil_untuk_tamu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('notifications.index'), false);
    }
}
