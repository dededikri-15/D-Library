<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Penerbit Uji',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            'website' => 'https://contoh.test',
            'email' => 'halo@contoh.test',
            'phone' => '021-1234567',
        ], $overrides);
    }

    public function test_pustakawan_can_open_publisher_index(): void
    {
        Publisher::factory()->create(['name' => 'Penerbit Tampil']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('publishers.index'))
            ->assertOk()
            ->assertSee('Penerbit Tampil')
            ->assertSee('Tambah penerbit');
    }

    public function test_pustakawan_can_open_publisher_create_form(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('publishers.create'))
            ->assertOk()
            ->assertSee('Tambah Penerbit');
    }

    public function test_pustakawan_can_create_publisher(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload())
            ->assertRedirect(route('publishers.index'));

        $this->assertDatabaseHas('publishers', [
            'name' => 'Penerbit Uji',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            'website' => 'https://contoh.test',
        ]);
    }

    /**
     * Email dan telepon ada di form dan sudah divalidasi `PublisherRequest`,
     * tapi sebelum Task 16 kolomnya tidak pernah ada di tabel dan tidak
     * fillable. Input Pustakawan lolos validasi lalu dibuang oleh proteksi
     * mass-assignment, jadi flash sukses tetap muncul padahal data hilang.
     */
    public function test_publisher_contact_fields_are_actually_persisted(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload())
            ->assertRedirect();

        $publisher = Publisher::where('name', 'Penerbit Uji')->sole();

        $this->assertSame('halo@contoh.test', $publisher->email);
        $this->assertSame('021-1234567', $publisher->phone);
    }

    public function test_publisher_contact_is_optional(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), [
                'name' => 'Penerbit Minimal',
            ])
            ->assertRedirect();

        $publisher = Publisher::where('name', 'Penerbit Minimal')->sole();

        $this->assertNull($publisher->email);
        $this->assertNull($publisher->phone);
    }

    public function test_pustakawan_can_open_publisher_edit_form(): void
    {
        $publisher = Publisher::factory()->create([
            'name' => 'Penerbit Diedit',
            'email' => 'kontak@contoh.test',
            'phone' => '021-7654321',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('publishers.edit', $publisher))
            ->assertOk()
            ->assertSee('Penerbit Diedit')
            ->assertSee('kontak@contoh.test')
            ->assertSee('021-7654321');
    }

    public function test_pustakawan_can_update_publisher(): void
    {
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Lama']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('publishers.update', $publisher), $this->payload([
                'name' => 'Penerbit Baru',
                'email' => 'baru@contoh.test',
                'phone' => '022-9998887',
            ]))
            ->assertRedirect(route('publishers.index'));

        $publisher->refresh();

        $this->assertSame('Penerbit Baru', $publisher->name);
        $this->assertSame('baru@contoh.test', $publisher->email);
        $this->assertSame('022-9998887', $publisher->phone);
    }

    public function test_publisher_update_can_clear_optional_fields(): void
    {
        $publisher = Publisher::factory()->create([
            'name' => 'Penerbit Dikosongkan',
            'email' => 'hapus@contoh.test',
            'phone' => '021-0000000',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('publishers.update', $publisher), [
                'name' => 'Penerbit Dikosongkan',
                'email' => null,
                'phone' => null,
            ])
            ->assertRedirect();

        $publisher->refresh();

        $this->assertNull($publisher->email);
        $this->assertNull($publisher->phone);
    }

    public function test_pustakawan_can_delete_publisher(): void
    {
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Dihapus']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->delete(route('publishers.destroy', $publisher))
            ->assertRedirect(route('publishers.index'));

        $this->assertDatabaseMissing('publishers', ['id' => $publisher->id]);
    }

    public function test_publisher_name_is_required(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('publishers', 0);
    }

    public function test_publisher_name_must_be_unique(): void
    {
        Publisher::factory()->create(['name' => 'Penerbit Kembar']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload(['name' => 'Penerbit Kembar']))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Publisher::where('name', 'Penerbit Kembar')->count());
    }

    public function test_publisher_website_must_be_a_valid_url(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload(['website' => 'bukan-url']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('publishers', 0);
    }

    public function test_publisher_email_must_be_valid(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('publishers.store'), $this->payload(['email' => 'bukan-email']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('publishers', 0);
    }

    public function test_anggota_cannot_reach_publisher_management(): void
    {
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Terlindungi']);

        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('publishers.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->anggota()->create())
            ->post(route('publishers.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs(User::factory()->anggota()->create())
            ->put(route('publishers.update', $publisher), $this->payload())
            ->assertForbidden();

        $this->actingAs(User::factory()->anggota()->create())
            ->delete(route('publishers.destroy', $publisher))
            ->assertForbidden();

        $this->assertDatabaseHas('publishers', ['name' => 'Penerbit Terlindungi']);
    }

    public function test_guest_is_redirected_from_publisher_management(): void
    {
        $publisher = Publisher::factory()->create();

        $this->get(route('publishers.index'))->assertRedirect(route('login'));
        $this->post(route('publishers.store'), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('publishers.destroy', $publisher))->assertRedirect(route('login'));

        $this->assertDatabaseHas('publishers', ['id' => $publisher->id]);
    }

    /**
     * `whereNumber` di route menolak id non numerik sebelum query. Tanpa itu
     * SQLite mengembalikan null dan 404 bersih, sementara PostgreSQL menolak
     * teks untuk kolom bigint dan melempar QueryException sampai HTTP 500.
     */
    public function test_non_numeric_publisher_id_returns_404_not_server_error(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get('/penerbit/abc')
            ->assertNotFound();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get('/penerbit/1abc')
            ->assertNotFound();
    }

    /**
     * Alamat adalah kolom ringkasan di tabel penerbit, bukan `description`.
     * Partial daftar dulu selalu membaca `description`, jadi alamat tidak
     * pernah tampil untuk dua dari tiga entitas yang memakai partial itu.
     */
    public function test_publisher_index_shows_address_as_subtitle(): void
    {
        Publisher::factory()->create([
            'name' => 'Penerbit Beralamat',
            'address' => 'Jl. Asia Afrika No. 112, Bandung',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('publishers.index'))
            ->assertOk()
            ->assertSee('Jl. Asia Afrika No. 112, Bandung');
    }

    public function test_author_index_shows_biography_as_subtitle(): void
    {
        Author::factory()->create([
            'name' => 'Penulis Berbio',
            'biography' => 'Lahir di Bandung dan menulis sejak 1998.',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('authors.index'))
            ->assertOk()
            ->assertSee('Lahir di Bandung dan menulis sejak 1998.');
    }
}
