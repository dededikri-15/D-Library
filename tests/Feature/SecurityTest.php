<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Pemeriksaan keamanan menyeluruh (Category 7).
 *
 * Setiap test di sini menjawab satu pertanyaan: "bisakah penyerang luar
 * melakukan hal yang seharusnya tidak boleh?" Kalau ada yang gagal, berarti
 * ada celah — bukan sekadar bug tampilan.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    /* ------------------------------------------------------------------
     | Task 7.1 — CSRF
     * ----------------------------------------------------------------- */

    public function test_csrf_middleware_is_active_on_write_routes(): void
    {
        /*
         * Catatan: assert 419 untuk request tanpa token TIDAK bisa ditulis di
         * feature test. Middleware ValidateCsrfToken sengaja melewati
         * pemeriksaan saat `app->runningUnitTests()` — perilaku bawaan Laravel
         * agar test tidak perlu token CSRF.
         *
         * Yang bisa dan penting diuji otomatis: setiap route yang mengubah
         * data berada di dalam middleware group `web`, dan group itulah yang
         * memuat ValidateCsrfToken. Pembuktian 419 yang sesungguhnya dilakukan
         * lewat smoke test ke server sungguhan.
         */
        $routes = collect(app('router')->getRoutes())
            ->filter(fn ($route) => array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== [])
            // Hanya route milik aplikasi ini. Laravel mendaftarkan route
            // Closure sendiri (mis. endpoint `storage/{path}`) yang memang
            // tidak memakai group `web`.
            ->filter(fn ($route) => str_starts_with((string) $route->getActionName(), 'App\\'));

        $this->assertGreaterThan(0, $routes->count());

        foreach ($routes as $route) {
            $this->assertContains(
                'web',
                $route->gatherMiddleware(),
                "Route [{$route->methods()[0]} {$route->uri()}] tidak berada di middleware group 'web'."
            );
        }
    }

    public function test_web_middleware_group_contains_csrf_protection(): void
    {
        $middleware = $this->app->make(Kernel::class);

        $groups = $middleware->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $groups, 'Middleware group `web` tidak ditemukan.');
        $this->assertContains(
            ValidateCsrfToken::class,
            $groups['web'],
            'Middleware group `web` tidak memuat ValidateCsrfToken.'
        );
    }

    /* ------------------------------------------------------------------
     | Task 7.2 — validasi input
     * ----------------------------------------------------------------- */

    public function test_non_numeric_loan_user_filter_does_not_crash_postgresql(): void
    {
        // `user_id` menuju kolom bigint. Tanpa validasi, PostgreSQL melempar
        // QueryException -> HTTP 500. Di SQLite test ini selalu lolos.
        $staff = User::factory()->pustakawan()->create();
        $first = User::factory()->anggota()->create();
        $second = User::factory()->anggota()->create();

        Loan::factory()->create(['user_id' => $first->id]);
        Loan::factory()->create(['user_id' => $second->id]);

        // Filter buruk diabaikan, jadi semua peminjaman tetap tampil.
        $this->actingAs($staff)
            ->get('/peminjaman?user_id=abc')
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 2);

        // Filter yang benar tetap bekerja.
        $this->actingAs($staff)
            ->get('/peminjaman?user_id='.$first->id)
            ->assertOk()
            ->assertViewHas('loans', fn ($loans) => $loans->total() === 1);
    }

    public function test_invalid_loan_status_filter_is_ignored_not_fatal(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get('/peminjaman?status=ngawur')
            ->assertOk();
    }

    public function test_invalid_book_status_filter_is_ignored_not_fatal(): void
    {
        $this->get('/buku?status=ngawur')->assertOk();
    }

    public function test_search_wildcards_are_treated_as_literal_characters(): void
    {
        Book::factory()->count(2)->create();

        // Tanpa escape LIKE, `%` akan membuat semua baris cocok.
        $this->get('/buku?q=%25')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->total() === 0);
    }

    public function test_overlong_search_term_is_ignored(): void
    {
        $this->get('/buku?q='.str_repeat('a', 500))->assertOk();
    }

    /* ------------------------------------------------------------------
     | Task 7.3 & 7.4 — XSS & escaping output
     * ----------------------------------------------------------------- */

    public function test_script_tags_in_input_are_escaped_in_output(): void
    {
        $payload = '<script>alert("xss")</script>';

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), $this->bookPayload(['title' => $payload]))
            ->assertRedirect();

        $book = Book::firstOrFail();

        $response = $this->get(route('books.show', $book));

        // Tag <script> harus muncul sebagai teks, bukan sebagai elemen HTML.
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_script_tags_in_search_are_escaped(): void
    {
        $this->get('/buku?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_no_blade_template_uses_raw_unescaped_output(): void
    {
        // `{!! !!` melempar escaping Blade dan jadi celah XSS. Whole-app
        // aman asal tidak ada satu pun pemakaian.
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                if (str_contains((string) file_get_contents($file->getPathname()), '{!!')) {
                    $offenders[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $offenders, 'Ditemukan output Blade tanpa escaping: '.implode(', ', $offenders));
    }

    public function test_security_headers_and_csp_nonce_are_applied(): void
    {
        $response = $this->get(route('home'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $policy = $response->headers->get('Content-Security-Policy');
        $nonce = Vite::cspNonce();

        $this->assertNotEmpty($nonce);
        $this->assertStringContainsString("script-src 'self' 'nonce-{$nonce}'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString('nonce="'.$nonce.'"', $response->getContent());
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
    }

    public function test_login_endpoint_uses_configured_named_rate_limit(): void
    {
        RateLimiter::clear('198.51.100.77');
        config(['perpustakaan.security.login_per_minute' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post('/login', ['email' => 'rate@test.invalid', 'password' => 'wrong'])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->post('/login', ['email' => 'rate@test.invalid', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    /* ------------------------------------------------------------------
     | Task 7.5 — password hashing
     * ----------------------------------------------------------------- */

    public function test_passwords_are_never_stored_in_plain_text(): void
    {
        $user = User::factory()->pustakawan()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->fresh()->password);
        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->password));
    }

    public function test_registered_user_password_is_hashed(): void
    {
        $this->post('/register', [
            'name' => 'Anggota Baru',
            'email' => 'baru@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'baru@perpustakaan.test')->firstOrFail();

        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /* ------------------------------------------------------------------
     | Task 7.6 & 7.7 — authorization & endpoint admin
     * ----------------------------------------------------------------- */

    public function test_anggota_cannot_reach_any_staff_or_admin_route(): void
    {
        $member = User::factory()->anggota()->create();

        $staffRoutes = [
            '/dashboard', '/buku/create', '/kategori', '/kategori/create',
            '/penulis', '/penulis/create', '/penerbit', '/penerbit/create',
            '/peminjaman',
            '/pengguna',
            '/pengguna/create',
        ];

        foreach ($staffRoutes as $uri) {
            $this->actingAs($member)
                ->get($uri)
                ->assertForbidden("Anggota seharusnya tidak bisa membuka {$uri}.");
        }
    }

    public function test_pustakawan_can_reach_user_management_routes(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)->get('/pengguna')->assertOk();
        $this->actingAs($staff)->get('/pengguna/create')->assertOk();

        $this->actingAs($staff)->get('/admin/pengguna')->assertNotFound();
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        foreach (['/dashboard', '/kategori', '/peminjaman', '/pengguna', '/favorit'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_write_actions_are_closed_to_guests(): void
    {
        $book = Book::factory()->create();

        $this->post('/peminjaman', [])->assertRedirect(route('login'));
        $this->post('/buku', [])->assertRedirect(route('login'));
        $this->put('/buku/'.$book->id, [])->assertRedirect(route('login'));
        $this->delete('/buku/'.$book->id)->assertRedirect(route('login'));
        $this->post('/favorit/'.$book->id)->assertRedirect(route('login'));
    }

    public function test_anggota_cannot_modify_data_with_direct_request(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['title' => 'Asli']);

        $this->actingAs($member)
            ->put(route('books.update', $book), $this->bookPayload(['title' => 'Diubah']))
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('books.destroy', $book))
            ->assertForbidden();

        $this->assertSame('Asli', $book->fresh()->title);
    }

    /* ------------------------------------------------------------------
     | Task 7.10 — proteksi file PDF
     * ----------------------------------------------------------------- */

    public function test_member_cannot_read_another_members_pdf(): void
    {
        $book = Book::factory()->create(['file' => 'books/digital.pdf']);

        $owner = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $owner->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $other = User::factory()->anggota()->create();

        $this->actingAs($other)
            ->get(route('books.file', $book))
            ->assertForbidden();
    }

    public function test_pdf_access_cannot_be_bypassed_through_the_storage_route(): void
    {
        $book = Book::factory()->create(['file' => 'books/rahasia.pdf']);

        $member = User::factory()->anggota()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'user_id' => $member->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        // Bahkan anggota yang berhak pun tidak boleh mengambil PDF lewat
        // /storage/{path} — berkasnya harus lewat controller yang memeriksa.
        $response = $this->actingAs($member)->get('/storage/books/rahasia.pdf');

        $this->assertNotSame(200, $response->getStatusCode());
    }

    /* ------------------------------------------------------------------
     | Task 7.11 — rate limiting
     * ----------------------------------------------------------------- */

    public function test_login_is_locked_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->pustakawan()->create(['password' => 'password']);
        $max = (int) config('perpustakaan.security.login_max_attempts');

        for ($i = 0; $i < $max; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        // Percobaan berikutnya harus ditolak meski kredensialnya benar.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_register_endpoint_is_rate_limited(): void
    {
        $limit = (int) config('perpustakaan.security.register_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->post('/register', [
                'name' => 'Massa '.$i,
                'email' => "massa{$i}@perpustakaan.test",
                'gender' => User::GENDER_LAKI_LAKI,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $this->post('/register', [
            'name' => 'Kelebihan',
            'email' => 'kelebihan@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'kelebihan@perpustakaan.test']);
    }

    public function test_pdf_route_is_rate_limited(): void
    {
        Storage::fake('local');

        $book = Book::factory()->create(['file' => 'books/digital.pdf']);
        Storage::disk('local')->put('books/digital.pdf', '%PDF-1.4 test');

        $limit = (int) config('perpustakaan.security.file_read_per_minute');
        $staff = User::factory()->pustakawan()->create();

        for ($i = 0; $i < $limit; $i++) {
            $this->actingAs($staff)->get(route('books.file', $book))->assertOk();
        }

        $this->actingAs($staff)
            ->get(route('books.file', $book))
            ->assertStatus(429);
    }

    /* ------------------------------------------------------------------
     | Task 7.12 — pemeriksaan menyeluruh
     * ----------------------------------------------------------------- */

    public function test_every_non_public_route_requires_authentication(): void
    {
        // `/up` memang route health check bawaan Laravel dan sengaja
        // terbuka; sisanya halaman publik yang memang harus bisa dibuka
        // tamu (PRD §4: katalog dan kategori terbuka tanpa login).
        //
        // `/buku/pencarian` ikut terbuka karena pratinjau hasil pencarian ada
        // di katalog publik — kalau endpoint ini butuh login, panelnya akan
        // selalu gagal untuk tamu. Data yang dikembalikannya sudah dibatasi
        // sama seperti katalog: buku nonaktif tersembunyi kecuali pemanggilnya
        // staff.
        $publicUris = ['/', '/login', '/register', '/buku', '/buku/pencarian', '/kategori-buku', '/up', '/forgot-password', '/reset-password'];

        $checked = 0;

        foreach (app('router')->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = '/'.ltrim($route->uri(), '/');

            if (in_array($uri, $publicUris, true) || str_contains($uri, '{')) {
                continue;
            }

            $this->get($uri)->assertRedirect(
                route('login'),
                "Route GET {$uri} seharusnya butuh login."
            );

            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
    }

    public function test_idempotency_of_logout_does_not_leak_session(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect();
        $this->assertGuest();

        // Request logout kedua tidak boleh membuat error.
        $this->post('/logout')->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------
     | Helper
     * ----------------------------------------------------------------- */

    /**
     * @return array<string, mixed>
     */
    protected function bookPayload(array $overrides = []): array
    {
        $book = Book::factory()->make();

        return array_merge([
            'title' => 'Buku Uji Keamanan',
            'isbn' => '978-602-000-000-1',
            'publication_year' => 2024,
            'category_id' => $book->category_id,
            'author_id' => $book->author_id,
            'publisher_id' => $book->publisher_id,
            'status' => Book::STATUS_AVAILABLE,
        ], $overrides);
    }
}
