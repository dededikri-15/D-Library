<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryDeleteAndUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_pustakawan_can_delete_category(): void
    {
        $category = Category::factory()->create(['name' => 'Kategori Dihapus']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_anggota_cannot_create_category(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->post(route('categories.store'), ['name' => 'Menyusup'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Menyusup']);
    }

    public function test_pustakawan_can_create_user(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('users.store'), [
                'name' => 'Anggota Baru',
                'email' => 'anggota.baru@example.com',
                'gender' => User::GENDER_PEREMPUAN,
                'date_of_birth' => '1998-08-15',
                'password' => 'rahasia-kuat-123',
                'password_confirmation' => 'rahasia-kuat-123',
                'role' => User::ROLE_ANGGOTA,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'anggota.baru@example.com',
            'role' => User::ROLE_ANGGOTA,
            'gender' => User::GENDER_PEREMPUAN,
        ]);
    }

    public function test_user_forms_show_gender_and_current_choice(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $member = User::factory()->anggota()->create([
            'gender' => User::GENDER_PEREMPUAN,
        ]);

        $this->actingAs($staff)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('name="gender"', false)
            ->assertSee('name="date_of_birth"', false)
            ->assertSee('Laki-laki')
            ->assertSee('Perempuan');

        $this->actingAs($staff)
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('value="perempuan" selected', false)
            ->assertSee('name="date_of_birth"', false);
    }
}
