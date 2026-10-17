<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
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
}
