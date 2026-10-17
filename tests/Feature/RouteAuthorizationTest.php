<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\ProvidesSecurityBookPayload;
use Tests\TestCase;

class RouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
    }

    use ProvidesSecurityBookPayload;

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
}
