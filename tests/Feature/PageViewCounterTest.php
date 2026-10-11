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
            ->assertSee('Halaman ini dibuka 1 kali', false)
            ->assertSee('Total kunjungan 1', false);

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

    public function test_total_counts_every_tracked_page(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('books.index'))->assertOk();
        $this->get(route('books.index'))->assertOk();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Total kunjungan 4', false);
    }

    public function test_staff_pages_are_not_recorded(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)->get(route('loans.index'))->assertOk();

        $this->assertDatabaseMissing('page_views', ['route_name' => 'loans.index']);
        $this->assertSame(0, (int) DB::table('page_views')->sum('view_count'));
    }

    public function test_untracked_page_shows_only_total(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Halaman ini dibuka', false)
            ->assertSee('Total kunjungan', false);
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

    public function test_english_footer_picks_singular_and_plural(): void
    {
        // Sesuai LocalePreferenceTest: bahasa dipilih lewat session 'locale',
        // yang dibaca middleware SetLocale sebelum footer dirender.
        $this->withSession(['locale' => 'en'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('This page has been opened 1 time', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('This page has been opened 2 times', false);
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

    public function test_member_page_footer_shows_page_counter(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Halaman ini dibuka 1 kali', false)
            ->assertSee('Total kunjungan 1', false);
    }

    public function test_guest_opening_member_page_is_not_counted(): void
    {
        // Middleware ini berjalan sebelum `auth`, jadi tanpa cek user tamu
        // yang membuka /favorit tetap terhitung walau cuma dilempar ke login.
        $this->get(route('favorites.index'))->assertRedirect(route('login'));

        $this->assertSame(0, (int) DB::table('page_views')->sum('view_count'));
    }
}
