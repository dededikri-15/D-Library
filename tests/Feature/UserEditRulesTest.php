<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserEditRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pustakawan_cannot_assign_removed_admin_role(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('users.update', $member), [
                'name' => $member->name,
                'email' => $member->email,
                'gender' => $member->gender,
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ANGGOTA, $member->fresh()->role);
    }

    public function test_pustakawan_can_change_user_role(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $anggota = User::factory()->anggota()->create();

        $this->actingAs($staff)
            ->put(route('users.update', $anggota), [
                'name' => $anggota->name,
                'email' => $anggota->email,
                'gender' => User::GENDER_PEREMPUAN,
                'role' => User::ROLE_PUSTAKAWAN,
            ])
            ->assertRedirect();

        $this->assertSame(User::ROLE_PUSTAKAWAN, $anggota->fresh()->role);
        $this->assertSame(User::GENDER_PEREMPUAN, $anggota->fresh()->gender);
    }

    public function test_editing_user_without_password_keeps_existing_password(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $anggota = User::factory()->anggota()->create();
        $originalHash = $anggota->password;

        $this->actingAs($staff)
            ->put(route('users.update', $anggota), [
                'name' => 'Nama Baru',
                'email' => $anggota->email,
                'gender' => $anggota->gender,
                'role' => User::ROLE_ANGGOTA,
            ])
            ->assertRedirect();

        $anggota->refresh();
        $this->assertSame('Nama Baru', $anggota->name);
        $this->assertSame($originalHash, $anggota->password);
    }

    public function test_pustakawan_cannot_delete_own_account(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->delete(route('users.destroy', $staff))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }
}
