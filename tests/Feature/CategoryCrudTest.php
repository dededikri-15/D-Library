<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_crud_and_auto_slug(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->post(route('categories.store'), [
                'name' => 'Ilmu Komputer',
                'description' => 'Buku seputar komputer.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Ilmu Komputer', 'slug' => 'ilmu-komputer']);
    }

    public function test_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'fiksi', 'name' => 'Fiksi']);
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->post(route('categories.store'), ['name' => 'Fiksi', 'slug' => 'fiksi'])
            ->assertSessionHasErrors('slug');
    }

    /**
     * `categories.name` unik di database. Tanpa aturan `unique` di
     * `CategoryRequest`, nama kembar lolos validasi lalu meledak jadi
     * QueryException dan HTTP 500, bukan pesan error yang bisa dibaca.
     */
    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('categories.store'), ['name' => 'Sains', 'slug' => 'sains-kembar'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Category::where('name', 'Sains')->count());
        $this->assertDatabaseMissing('categories', ['slug' => 'sains-kembar']);
    }

    public function test_pustakawan_can_update_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Sains Lama',
            'slug' => 'sains-lama',
            'description' => 'Deskripsi lama.',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('categories.update', $category), [
                'name' => 'Sains Baru',
                'slug' => 'sains-baru',
                'description' => 'Deskripsi baru.',
            ])
            ->assertRedirect(route('categories.index'));

        $category->refresh();

        $this->assertSame('Sains Baru', $category->name);
        $this->assertSame('sains-baru', $category->slug);
        $this->assertSame('Deskripsi baru.', $category->description);
    }

    public function test_category_update_keeps_own_slug(): void
    {
        $category = Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('categories.update', $category), [
                'name' => 'Sains',
                'slug' => 'sains',
            ])
            ->assertRedirect();

        $this->assertSame('sains', $category->fresh()->slug);
        $this->assertSame('Sains', $category->fresh()->name);
    }
}
