<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BanUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_ban_permission_can_toggle_banned_flag(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ban_user',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $actor->givePermissionTo($permission);

        $target = User::factory()->create(['banned' => false]);

        $this->actingAs($actor)
            ->post(route('users.ban', $target->id))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'banned' => 1,
        ]);

        $this->actingAs($actor)
            ->post(route('users.ban', $target->id))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'banned' => 0,
        ]);
    }

    public function test_user_without_permission_cannot_ban(): void
    {
        Permission::firstOrCreate([
            'name' => 'ban_user',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create();
        $target = User::factory()->create(['banned' => false]);

        $this->actingAs($actor)
            ->post(route('users.ban', $target->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'banned' => 0,
        ]);
    }

    public function test_user_cannot_ban_self(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ban_user',
            'guard_name' => 'web',
        ]);

        $actor = User::factory()->create(['banned' => false]);
        $actor->givePermissionTo($permission);

        $this->actingAs($actor)
            ->post(route('users.ban', $actor->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $actor->id,
            'banned' => 0,
        ]);
    }
}
