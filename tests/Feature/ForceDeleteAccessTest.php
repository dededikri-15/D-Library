<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForceDeleteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_menampilkan_catatan_hanya_pada_baris_yang_dipakai(): void
    {
        $dipakai = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        Book::factory()->count(2)->create(['category_id' => $dipakai->id]);
        Category::create(['name' => 'Sains', 'slug' => 'sains']);

        $content = $this->actingAs($this->librarian())
            ->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        // Baris yang tidak dipakai bukunya tidak boleh ikut menyebut jumlah
        // buku, jadi kalimat ini harus muncul tepat satu kali.
        $this->assertSame(1, substr_count(
            $content,
            '2 buku ikut terhapus permanen, beserta file dan riwayat peminjamannya.',
        ));
        $this->assertStringContainsString('Sains', $content);
    }

    public function test_area_hapus_tidak_boleh_di_dalam_form_edit(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);

        $html = $this->actingAs($this->librarian())
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->getContent();

        // <form> bersarang = browser membuang tag form kedua, dan tombol hapus
        // ikut jadi milik form update. Efeknya, menekan "Hapus kategori" bisa
        // mengirim update, bukan delete.
        preg_match_all('/<(\/?)form\b[^>]*>/i', $html, $matches, PREG_SET_ORDER);

        $depth = 0;
        foreach ($matches as $match) {
            if ($match[1] === '/') {
                $depth = max(0, $depth - 1);

                continue;
            }

            $this->assertSame(0, $depth, "Ada <form> bersarang: {$match[0]}");
            $depth++;
        }
    }

    public function test_anggota_tidak_bisa_menghapus_master_data(): void
    {
        $category = Category::create(['name' => 'Fiksi', 'slug' => 'fiksi']);
        $book = Book::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ANGGOTA]))
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
