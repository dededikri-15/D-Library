<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kotak statistik di dasbor pustakawan bisa diklik.
 *
 * Yang diuji bukan "ada tautannya", tapi dua hal yang tidak terlihat dari
 * markup:
 *
 * 1. Tujuannya benar-benar bisa dibuka pustakawan (bukan 403/500), dan
 *    daftarnya benar-benar berisi isi yang angkanya.
 * 2. Angkanya di kotak dan isi daftarnya menghitung hal yang SAMA. Kotak
 *    "Anggota" yang menautkan ke seluruh pengguna, atau "Peminjaman Aktif"
 *    yang hanya menampilkan status `borrowed` sementara angkanya menghitung
 *    `borrowed + overdue`, membuat dasbor berbohong.
 */
class DashboardStatLinksTest extends TestCase
{
    use RefreshDatabase;

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function statLinks(): array
    {
        return [
            'total buku' => ['Total Buku', 'books.index'],
            'pengguna' => ['Pengguna', 'users.index'],
            'anggota' => ['Anggota', 'users.index'],
            'total peminjaman' => ['Total Peminjaman', 'loans.index'],
            'peminjaman aktif' => ['Peminjaman Aktif', 'loans.index'],
        ];
    }

    /**
     * Setiap kotak harus punya `href` sendiri. Kalau dua kotak berbagi URL,
     * klik pada salah satunya mengarahkan orang ke daftar yang tidak
     * menjelaskan angkanya.
     */
    public function test_setiap_kotak_stat_punya_tautan_yang_berbeda(): void
    {
        $content = $this->actingAs($this->librarian())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*class="stat-card/', $content, $matches);

        $this->assertCount(5, $matches[1], 'Harus ada lima kotak stat yang bisa diklik.');
        $this->assertCount(5, array_unique($matches[1]), 'Tiap kotak harus punya URL sendiri.');

        foreach (array_column(self::statLinks(), 0) as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    public function test_kotak_stat_menunjuk_ke_daftar_yang_bisa_dibuka_pustakawan(): void
    {
        $librarian = $this->librarian();

        $expected = [
            route('books.index'),
            route('users.index'),
            route('users.index', ['role' => User::ROLE_ANGGOTA]),
            route('loans.index'),
            route('loans.index', ['active' => 1]),
        ];

        $content = $this->actingAs($librarian)->get(route('dashboard'))->getContent();

        foreach ($expected as $url) {
            $this->assertStringContainsString($url, $content, "Kotak stat tidak menunjuk ke {$url}.");
            $this->actingAs($librarian)->get($url)->assertOk();
        }
    }

    /**
     * "Bisa diklik" harus berarti orang sampai ke isi angkanya, bukan ke
     * halaman yang kebetulan juga menyebut angka itu.
     */
    public function test_klik_total_buku_membuka_daftar_judul_buku(): void
    {
        Book::factory()->create(['title' => 'Judul Buku yang Dicari']);
        Book::factory()->count(2)->create();

        $this->actingAs($this->librarian())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Judul Buku yang Dicari');
    }

    public function test_klik_anggota_menampilkan_anggota_saja(): void
    {
        User::factory()->pustakawan()->count(2)->create();
        User::factory()->anggota()->create(['name' => 'Anggota Tunggal']);
        User::factory()->create(['name' => 'Anggota Tersaring', 'role' => User::ROLE_PUSTAKAWAN]);

        $response = $this->actingAs($this->librarian())
            ->get(route('users.index', ['role' => User::ROLE_ANGGOTA]))
            ->assertOk();

        $response->assertSee('Anggota Tunggal');
        $response->assertDontSee('Anggota Tersaring');
    }

    /**
     * Angka "Peminjaman Aktif" di dasbor menghitung `borrowed + overdue`
     * (`DashboardController`). Filter `active=1` harus memakai definisi yang
     * sama persis, kalau tidak kartu dan daftarnya menampilkan dua jumlah
     * berbeda untuk hal yang sama.
     */
    public function test_klik_peminjaman_aktif_menampilkan_peminjaman_aktif(): void
    {
        Loan::factory()->count(2)->create(['status' => Loan::STATUS_BORROWED]);
        Loan::factory()->create(['status' => Loan::STATUS_OVERDUE]);
        Loan::factory()->count(3)->returned()->create();

        $dashboard = $this->actingAs($this->librarian())->get(route('dashboard'))->assertOk();
        $dashboard->assertViewHas('activeLoans', 3);

        $loans = $this->actingAs($this->librarian())
            ->get(route('loans.index', ['active' => 1]))
            ->assertOk();

        $loans->assertViewHas('loans', fn ($paginator) => $paginator->total() === 3);
        $loans->assertSee('Menampilkan peminjaman yang dipinjam dan terlambat saja.');
        $loans->assertSee(route('loans.index'));
    }

    public function test_filter_aktif_membuang_yang_sudah_dikembalikan(): void
    {
        Loan::factory()->create(['status' => Loan::STATUS_BORROWED]);
        Loan::factory()->create(['status' => Loan::STATUS_RETURNED]);

        $this->actingAs($this->librarian())
            ->get(route('loans.index', ['active' => 1]))
            ->assertOk()
            ->assertViewHas('loans', fn ($paginator) => $paginator->total() === 1);
    }

    /**
     * `?active=0` berarti "jangan filter" — bukan "tampilkan yang tidak
     * aktif". Kalau tidak, URL hasil ketik-paste tangan bisa membuat daftar
     * kosong tanpa penjelasan.
     *
     * Filter `active` juga tidak boleh punya checkbox di form: status di
     * database hanya ada tiga, dan "aktif" bukan salah satunya. Checkbox
     * untuk itu akan membuat orang mengira itu status keempat.
     */
    public function test_filter_aktif_hanya_menerima_nilai_satu(): void
    {
        Loan::factory()->create(['status' => Loan::STATUS_BORROWED]);
        Loan::factory()->returned()->create();

        $this->actingAs($this->librarian())
            ->get(route('loans.index', ['active' => 0]))
            ->assertOk()
            ->assertViewHas('loans', fn ($paginator) => $paginator->total() === 2)
            ->assertViewHas('onlyActive', false)
            ->assertDontSee('name="active"', false);
    }

    /**
     * Nilai status di database berbahasa Inggris (`borrowed`) karena migration
     * tidak boleh bergantung pada bahasa — tapi yang tampil ke pengguna harus
     * bahasa Indonesia. Dropdown yang menampilkan "borrowed / returned /
     * overdue" terlihat seperti bug, bukan fitur.
     */
    public function test_dropdown_status_menampilkan_label_indonesia(): void
    {
        $content = $this->actingAs($this->librarian())
            ->get(route('loans.index'))
            ->assertOk()
            ->getContent();

        foreach (['Dipinjam', 'Dikembalikan', 'Terlambat'] as $label) {
            $this->assertStringContainsString($label, $content);
        }

        foreach (Loan::statuses() as $status) {
            $this->assertStringNotContainsString(
                '>'.$status.'</option>',
                $content,
                "Nilai mentah \"{$status}\" bocor ke dropdown.",
            );
        }
    }

    public function test_anggota_tidak_bisa_mengikuti_tautan_dasbor(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($member)->get(route('users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('loans.index'))->assertForbidden();
    }
}
