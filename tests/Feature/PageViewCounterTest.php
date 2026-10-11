<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageViewCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_visit_is_recorded_and_shown_in_footer(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Dilihat: 1', false);

        $this->assertDatabaseHas('page_views', ['route_name' => 'home', 'view_count' => 1]);
    }

    public function test_catalog_and_detail_page_share_their_own_counters(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.index'))->assertOk();
        $this->get(route('books.show', $book))->assertOk();
        $this->get(route('books.show', $book))->assertOk();

        $this->assertDatabaseHas('page_views', ['route_name' => 'books.index', 'view_count' => 1]);
        // Dua buku berbeda dianggap satu fitur "detail buku".
        $this->assertDatabaseHas('page_views', ['route_name' => 'books.show', 'view_count' => 2]);
    }

    public function test_each_page_shows_its_own_counter(): void
    {
        // Kunjungan tiap menu dihitung terpisah. Pencatatan middleware jalan
        // SEBELUM view dirender, jadi kunjungan yang sedang berlangsung sudah
        // termasuk di angka yang tampil.
        $this->get(route('home'))->assertOk();          // beranda = 1
        $this->get(route('books.index'))->assertOk();    // katalog = 1
        $this->get(route('books.index'))->assertOk();    // katalog = 2

        // Beranda dibuka lagi (kunjungan ke-2) → footer beranda tampil "Dilihat: 2".
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Dilihat: 2', false);

        // Katalog dibuka lagi (kunjungan ke-3) → footer katalog tampil "Dilihat: 3".
        // Angka beranda tetap 2 — tidak ikut naik karena katalog dikunjungi.
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Dilihat: 3', false);
    }

    public function test_staff_pages_are_not_recorded(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)->get(route('loans.index'))->assertOk();

        $this->assertDatabaseMissing('page_views', ['route_name' => 'loans.index']);
        $this->assertSame(0, (int) DB::table('page_views')->sum('view_count'));
    }

    public function test_untracked_page_hides_counter(): void
    {
        // Halaman yang tidak dipantau (login) tidak punya hitungan menu, jadi
        // bar Dilihat disembunyikan sama sekali di footer.
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Dilihat', false);
    }

    public function test_failed_requests_are_not_counted(): void
    {
        // Id non-numerik tidak match rute `books.show` sama sekali (lihat
        // whereNumber di routes/web.php), jadi route name-nya null dan
        // middleware tidak pernah mencatatnya.
        $this->get('/buku/abc')->assertNotFound();

        $this->assertSame(0, (int) DB::table('page_views')->sum('view_count'));
    }

    public function test_navigation_between_pages_is_counted_once_each(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('books.index'))->assertOk();

        $this->assertSame(2, (int) DB::table('page_views')->sum('view_count'));
    }

    public function test_english_footer_shows_post_views_bar(): void
    {
        // Sesuai LocalePreferenceTest: bahasa dipilih lewat session 'locale',
        // yang dibaca middleware SetLocale sebelum footer dirender. Default
        // bahasa Indonesia "Dilihat"; saat locale en berubah jadi "Post Views".
        $this->withSession(['locale' => 'en'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Post Views: 1', false);
    }

    public function test_member_menu_pages_are_counted_when_logged_in(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('loans.mine'))->assertOk();
        $this->actingAs($member)->get(route('favorites.index'))->assertOk();
        $this->actingAs($member)->get(route('reading-histories.index'))->assertOk();
        $this->actingAs($member)->get(route('library-cards.show'))->assertOk();

        $this->assertDatabaseHas('page_views', ['route_name' => 'loans.mine', 'view_count' => 1]);
        $this->assertDatabaseHas('page_views', ['route_name' => 'favorites.index', 'view_count' => 1]);
        $this->assertDatabaseHas('page_views', ['route_name' => 'reading-histories.index', 'view_count' => 1]);
        $this->assertDatabaseHas('page_views', ['route_name' => 'library-cards.show', 'view_count' => 1]);
    }

    public function test_member_page_footer_shows_post_views(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Dilihat: 1', false);
    }

    public function test_guest_opening_member_page_is_not_counted(): void
    {
        // Middleware ini berjalan sebelum `auth`, jadi tanpa cek user tamu
        // yang membuka /favorit tetap terhitung walau cuma dilempar ke login.
        $this->get(route('favorites.index'))->assertRedirect(route('login'));

        $this->assertSame(0, (int) DB::table('page_views')->sum('view_count'));
    }
}
