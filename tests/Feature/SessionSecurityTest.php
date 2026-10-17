<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
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
}
