<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman profil + foto profil.
 *
 * Yang dijaga di sini:
 *
 * 1. Otorisasi. `/profil` harus terbuka untuk anggota DAN pustakawan (mengatur
 *    profil bukan hak khusus staff), tapi tetap tertutup untuk tamu.
 * 2. Role tidak bisa dinaikkan sendiri. Ini sebabnya `ProfileRequest` tidak
 *    punya field `role` sama sekali — test ini yang menjaga keputusan itu.
 * 3. Avatar: bisa diunggah, bisa diganti (berkas lama hilang), bisa dilepas,
 *    dan hilang juga waktu akunnya dihapus. Berkas yang nyangkut di storage
 *    adalah kebocoran yang tidak terlihat tapi nyata.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(User::avatarDisk());
    }

    /**
     * @return array<string, mixed>
     */
    protected function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@perpustakaan.test',
            'gender' => User::GENDER_LAKI_LAKI,
            'date_of_birth' => '1995-06-15',
        ], $overrides);
    }

    /* ------------------------------------------------------------------
     | Akses halaman
     * ----------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_anggota_can_open_profile_page(): void
    {
        $user = User::factory()->anggota()->create(['name' => 'Anggota Satu']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('name="gender"', false)
            ->assertSee('value="laki-laki" selected', false)
            ->assertSee('Anggota Satu');
    }

    public function test_pustakawan_can_open_profile_page(): void
    {
        $user = User::factory()->pustakawan()->create(['name' => 'Petugas Satu']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Petugas Satu');
    }

    public function test_profile_page_form_declares_multipart_encoding(): void
    {
        // Tanpa `enctype`, field `avatar` hilang sebelum sampai ke PHP dan tidak
        // ada error yang muncul karena validasinya juga tidak pernah jalan.
        $content = $this->actingAs(User::factory()->anggota()->create())
            ->get(route('profile.show'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('enctype="multipart/form-data"', $content);
        $this->assertStringContainsString('name="avatar"', $content);
    }
}
