<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('');
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
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

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
}
