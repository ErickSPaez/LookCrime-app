<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProtectedAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_cannot_be_deleted(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name_en' => 'Admin', 'name_pt' => 'Admin']
        );

        $permission = Permission::firstOrCreate([
            'name' => 'delete_role',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $actor->givePermissionTo($permission);

        $this->actingAs($actor)
            ->delete(route('settings.roles.destroy', $role->name))
            ->assertRedirect(route('settings.roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_admin_role_cannot_be_edited(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name_en' => 'Admin', 'name_pt' => 'Admin']
        );

        $permission = Permission::firstOrCreate([
            'name' => 'edit_role',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $actor->givePermissionTo($permission);

        $this->actingAs($actor)
            ->get(route('settings.roles.edit', $role->name))
            ->assertRedirect(route('settings.roles.index'))
            ->assertSessionHas('error');
    }
}
