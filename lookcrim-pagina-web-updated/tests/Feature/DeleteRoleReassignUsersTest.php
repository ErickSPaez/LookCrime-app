<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeleteRoleReassignUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_role_reassigns_users_with_no_other_roles_to_user(): void
    {
        $fallback = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['name_en' => 'User', 'name_pt' => 'User']
        );

        $tempRole = Role::create([
            'name' => 'temp_role',
            'guard_name' => 'web',
            'name_en' => 'Temp',
            'name_pt' => 'Temp',
        ]);

        $deletePermission = Permission::firstOrCreate([
            'name' => 'delete_role',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $actor->givePermissionTo($deletePermission);

        $target = User::factory()->create();
        $target->syncRoles([$tempRole->name]);

        $this->actingAs($actor)
            ->delete(route('settings.roles.destroy', $tempRole->name))
            ->assertRedirect(route('settings.roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $tempRole->id]);

        $target->refresh();
        $this->assertTrue($target->hasRole($fallback->name));
        $this->assertFalse($target->hasRole('temp_role'));
    }

    public function test_deleting_role_does_not_force_user_role_if_user_has_other_roles(): void
    {
        Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['name_en' => 'User', 'name_pt' => 'User']
        );

        $otherRole = Role::create([
            'name' => 'other_role',
            'guard_name' => 'web',
            'name_en' => 'Other',
            'name_pt' => 'Other',
        ]);

        $tempRole = Role::create([
            'name' => 'temp_role2',
            'guard_name' => 'web',
            'name_en' => 'Temp2',
            'name_pt' => 'Temp2',
        ]);

        $deletePermission = Permission::firstOrCreate([
            'name' => 'delete_role',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $actor->givePermissionTo($deletePermission);

        $target = User::factory()->create();
        $target->syncRoles([$otherRole->name, $tempRole->name]);

        $this->actingAs($actor)
            ->delete(route('settings.roles.destroy', $tempRole->name))
            ->assertRedirect(route('settings.roles.index'));

        $target->refresh();
        $this->assertTrue($target->hasRole('other_role'));
        $this->assertFalse($target->hasRole('temp_role2'));
    }
}
