<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pesan error validasi yang benar-benar dilihat pengguna.
 *
 * Dua kelas bug yang diuji di sini keduanya "tidak terlihat" dari sisi kode:
 *
 * 1. **Kunci mentah bocor ke layar.** Aplikasi berjalan dengan `APP_LOCALE=id`,
 *    tapi tidak ada folder `lang/`. Saat kunci terjemahan tidak ditemukan,
 *    Translator mengembalikan kuncinya apa adanya — jadi yang tampil di layar
 *    adalah literal `validation.required`, bukan kalimat bahasa Indonesia.
 * 2. **Data yang tidak dinormalisasi lolos validasi.** Spasi di awal/akhir,
 *    huruf besar pada email, dan ISBN berisi teks bebas semuanya lolos aturan
 *    `string|max`, padahal akibatnya nyata: email huruf besar tidak bisa login
 *    (kolomnya disimpan lowercase), " Fiksi " lolos unique check dan muncul
 *    sebagai kategori kembar, dan katalog bisa berisi ISBN "bukan isbn".
 */
class ValidationErrorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Form yang reached saat kosong. `role` menentukan siapa yang mengisi.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function forms(): array
    {
        return [
            'daftar' => ['/register', 'guest'],
            'login' => ['/login', 'guest'],
            'lupa kata sandi' => ['/forgot-password', 'guest'],
            'buku' => ['/buku', 'staff'],
            'kategori' => ['/kategori', 'staff'],
            'penulis' => ['/penulis', 'staff'],
            'penerbit' => ['/penerbit', 'staff'],
            'pengguna' => ['/pengguna', 'staff'],
            'peminjaman' => ['/peminjaman', 'staff'],
            'riwayat baca' => ['/riwayat-baca', 'member'],
        ];
    }

    /**
     * @param  array<string, string>  $payload
     */
    #[DataProvider('forms')]
    public function test_pesan_validasi_bukan_kunci_mentah(string $url, string $role, array $payload = []): void
    {
        $this->actingAsRole($role)->post($url, $payload)->assertSessionHasErrors();

        $messages = session('errors')->getBag('default')->all();

        $this->assertNotEmpty($messages, "Form {$url} tidak menghasilkan pesan error sama sekali.");

        foreach ($messages as $message) {
            $this->assertDoesNotMatchRegularExpression(
                '/^(validation|passwords|auth)\.[a-z0-9_.]+$/',
                $message,
                "Pesan error pada {$url} membocorkan kunci terjemahan mentah: {$message}",
            );

            $this->assertStringContainsString(
                ' ',
                $message,
                "Pesan error pada {$url} bukan kalimat utuh, kemungkinan nama field mentah: {$message}",
            );
        }
    }

    public function test_pesan_lupa_kata_sandi_dalam_bahasa_indonesia(): void
    {
        $this->post('/forgot-password', ['email' => 'bukan@ada.example.com'])
            ->assertSessionHasErrors('email');

        $message = session('errors')->getBag('default')->first('email');

        $this->assertStringContainsString('tidak menemukan', $message);
    }

    public function test_isbn_berupa_teks_sampah_ditolak(): void
    {
        $staff = $this->librarian();
        $book = $this->validBookPayload(['isbn' => 'bukan isbn']);

        $this->actingAs($staff)->post('/buku', $book)
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_isbn_dengan_tanda_hubung_diterima(): void
    {
        $staff = $this->librarian();

        $this->actingAs($staff)->post('/buku', $this->validBookPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * ISBN-10 lama masih lazim; digit terakhirnya boleh huruf X.
     */
    public function test_isbn_10_dengan_huruf_x_diterima(): void
    {
        $staff = $this->librarian();

        $this->actingAs($staff)->post('/buku', $this->validBookPayload(['isbn' => '602000999x']))
            ->assertSessionHasNoErrors();

        // Huruf kecil dinormalkan ke huruf besar supaya unique tidak lolos dua kali.
        $this->assertDatabaseHas('books', ['isbn' => '602000999X']);
    }

    public function test_isbn_huruf_kecil_x_menyatakan_benturan_unik(): void
    {
        $staff = $this->librarian();

        $this->actingAs($staff)->post('/buku', $this->validBookPayload(['isbn' => '602000999X']))
            ->assertSessionHasNoErrors();

        $this->actingAs($staff)->post('/buku', $this->validBookPayload(['isbn' => '602000999x']))
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_login_dengan_email_huruf_besar_tetap_berhasil(): void
    {
        $user = User::factory()->create(['email' => 'anggota@contoh.test']);

        $this->post('/login', [
            'email' => 'Anggota@Contoh.test',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_pendaftaran_dengan_email_huruf_besar_disimpan_kecil(): void
    {
        $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'Budi@Contoh.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'budi@contoh.test']);
    }

    public function test_email_kembar_hanya_beda_huruf_besar_ditolak(): void
    {
        User::factory()->create(['email' => 'budi@contoh.test']);

        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'BUDI@CONTOH.TEST',
            'gender' => User::GENDER_LAKI_LAKI,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_kelola_pengguna_menormalkan_email(): void
    {
        $librarian = $this->librarian();
        $member = User::factory()->create(['email' => 'lama@contoh.test']);

        $this->actingAs($librarian)
            ->put(route('users.update', $member), [
                'name' => $member->name,
                'email' => 'Baru@Contoh.test',
                'gender' => $member->gender,
                'role' => 'anggota',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'email' => 'baru@contoh.test']);
    }

    public function test_nama_dengan_spasi_kembar_ditolak(): void
    {
        Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        // `TrimStrings` (middleware global Laravel) sudah memangkas spasi, jadi
        // nama kembar ini harus tertangkap aturan unique.
        $this->actingAs($this->librarian())
            ->post('/kategori', ['name' => '  Fiksi  '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_nama_non_latin_tidak_melempar_server_error(): void
    {
        $staff = $this->librarian();

        // Str::slug() tidak menghasilkan apa-apa untuk aksara CJK, jadi slug
        // kosong. Dua kategori seperti ini akan menabrak unique constraint slug
        // dan meledak jadi HTTP 500 — jadi yang diperiksa adalah redirect sukses,
        // bukan sekadar "tidak ada error di session".
        $this->actingAs($staff)->post('/kategori', ['name' => '文学'])->assertRedirect();
        $this->actingAs($staff)->post('/kategori', ['name' => '小説'])->assertRedirect();

        $this->assertSame(2, Category::count());
        $this->assertCount(2, array_unique(Category::pluck('slug')->all()));
    }

    public function test_pesan_validasi_quick_add_menyebut_nama_indonesia(): void
    {
        $response = $this->actingAs($this->librarian())
            ->postJson('/kategori/quick', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertStringContainsString('nama kategori', $response->json('errors.name.0'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validBookPayload(array $overrides = []): array
    {
        return [
            ...[
                'title' => 'Bumi Manusia',
                'isbn' => '978-602-001-999-1',
                'publication_year' => 2020,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => Category::factory()->create()->id,
                'author_id' => Author::factory()->create()->id,
                'publisher_id' => Publisher::factory()->create()->id,
            ],
            ...$overrides,
        ];
    }

    private function librarian(): User
    {
        return User::factory()->pustakawan()->create();
    }

    private function actingAsRole(string $role): self
    {
        $user = match ($role) {
            'staff' => User::factory()->pustakawan()->create(),
            'member' => User::factory()->create(),
            default => null,
        };

        return $user ? $this->actingAs($user) : $this;
    }
}
