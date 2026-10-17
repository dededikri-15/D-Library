<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Tests\Concerns\ProvidesSecurityBookPayload;
use Tests\TestCase;

class SecurityOutputSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    use ProvidesSecurityBookPayload;

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
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);

        /*
         * Regresi Task 11.9: `object-src 'none'` memblokir SEMUA <object>,
         * termasuk sematan PDF same-origin di halaman `books.read` — akibatnya
         * pembaca selalu menampilkan fallback "Browser ini tidak menampilkan
         * PDF secara langsung" walau stream-nya sehat (application/pdf +
         * Content-Disposition: inline). 'self' tetap menutup sumber luar.
         */
        $this->assertStringContainsString("object-src 'self'", $policy);
        $this->assertStringNotContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString('nonce="'.$nonce.'"', $response->getContent());
    }
}
