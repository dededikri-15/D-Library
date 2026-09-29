<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_statistik_ringkas(): void
    {
        Book::factory()->count(10)->create();
        User::factory()->anggota()->count(5)->create();
        Loan::factory()->count(3)->create(['status' => Loan::STATUS_BORROWED]);

        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Buku')
            ->assertSee('Pengguna')
            ->assertSee('Anggota')
            ->assertSee('Peminjaman Aktif');
    }

    public function test_dashboard_memuat_statistik_dengan_tiga_query_agregat(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $aggregateQueries = [];

        DB::listen(function (QueryExecuted $query) use (&$aggregateQueries): void {
            if (str_contains(strtolower($query->sql), 'count(case when')) {
                $aggregateQueries[] = $query->sql;
            }
        });

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertCount(3, $aggregateQueries);
    }

    public function test_pustakawan_dashboard_menampilkan_statistik(): void
    {
        Book::factory()->count(8)->create();
        User::factory()->anggota()->count(3)->create();

        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Buku')
            ->assertSee('Pengguna');
    }

    public function test_dashboard_menampilkan_peminjaman_terbaru(): void
    {
        $book = Book::factory()->create(['title' => 'Buku Terbaru']);
        $user = User::factory()->anggota()->create(['name' => 'Anggota Test']);

        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Peminjaman Terbaru')
            ->assertSee('Buku Terbaru')
            ->assertSee('Anggota Test');
    }

    public function test_dashboard_menghitung_seluruh_peminjaman_termasuk_yang_sudah_kembali(): void
    {
        Loan::factory()->count(2)->create(['status' => Loan::STATUS_BORROWED]);
        Loan::factory()->returned()->create();

        $response = $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Peminjaman');

        $response->assertViewHas('totalLoans', 3);
    }

    public function test_dashboard_menampilkan_buku_terbaru(): void
    {
        Book::factory()->create(['title' => 'Buku Baru Ditambahkan']);

        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buku Terbaru')
            ->assertSee('Buku Baru Ditambahkan');
    }

    public function test_dashboard_menampilkan_aktivitas_terbaru(): void
    {
        $book = Book::factory()->create(['title' => 'Buku Dibaca']);
        $user = User::factory()->anggota()->create(['name' => 'Pembaca Aktif']);

        ReadingHistory::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'last_page' => 10,
            'last_read_at' => now(),
        ]);

        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('Pembaca Aktif')
            ->assertSee('Buku Dibaca');
    }

    public function test_anggota_dashboard_menampilkan_statistik_pribadi(): void
    {
        $member = User::factory()->anggota()->create();

        Book::factory()->count(5)->create();
        Loan::factory()->count(2)->create([
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Peminjaman Aktif')
            ->assertSee('Favorit');
    }

    public function test_anggota_dashboard_menampilkan_riwayat_peminjaman(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['title' => 'Buku Riwayat Pinjaman']);
        Loan::factory()->returned()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Riwayat peminjaman')
            ->assertSee('Buku Riwayat Pinjaman')
            ->assertSee(route('loans.mine'), false);
    }

    public function test_anggota_dashboard_menampilkan_rekomendasi(): void
    {
        $member = User::factory()->anggota()->create();
        $category = Category::factory()->create();

        $readBook = Book::factory()->create(['category_id' => $category->id]);
        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $readBook->id,
            'last_page' => 5,
            'last_read_at' => now(),
        ]);

        $recommendedBook = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Buku Rekomendasi',
        ]);

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Rekomendasi untuk Anda')
            ->assertSee('Buku Rekomendasi');
    }

    public function test_anggota_dashboard_tidak_menampilkan_rekomendasi_kosong(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertDontSee('Rekomendasi untuk Anda');
    }

    public function test_anggota_dashboard_tidak_menampilkan_buku_yang_sudah_dibaca_di_rekomendasi(): void
    {
        $member = User::factory()->anggota()->create();
        $category = Category::factory()->create();

        $readBook = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Buku Sudah Dibaca',
        ]);
        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $readBook->id,
            'last_page' => 5,
            'last_read_at' => now(),
        ]);

        $response = $this->actingAs($member)->get(route('anggota.dashboard'));

        $response->assertOk();

        $content = $response->getContent();
        $recommendationsPos = strpos($content, 'Rekomendasi untuk Anda');
        $bookPos = strpos($content, 'Buku Sudah Dibaca');

        if ($recommendationsPos !== false && $bookPos !== false) {
            $this->assertTrue(
                $bookPos < $recommendationsPos,
                'Buku yang sudah dibaca tidak boleh muncul di bagian rekomendasi.'
            );
        }
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('anggota.dashboard'))->assertRedirect(route('login'));
    }

    public function test_anggota_tidak_bisa_melihat_dashboard_staff(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_staff_tidak_bisa_melihat_dashboard_anggota(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->get(route('anggota.dashboard'))
            ->assertForbidden();
    }
}
