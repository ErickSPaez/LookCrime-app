<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RegistersCityRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private function grant(User $user, array $permissions): void
    {
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            $user->givePermissionTo($permissionName);
        }
    }

    private function createRegisterAt(City $city, float $lat, float $lng, array $attrs = []): Register
    {
        $register = Register::create(array_merge([
            'title_pt' => 'Teste',
            'title_en' => 'Test',
            'content_pt' => 'Conteudo',
            'content_en' => 'Content',
            'category' => 'robo',
            'image' => '/storage/registers/test.jpg',
            'city_id' => $city->id,
        ], $attrs));

        DB::update(
            'UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$lng, $lat, $register->id]
        );

        return $register;
    }

    public function test_user_cannot_view_edit_or_delete_register_outside_city_even_with_permissions(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $cityB = City::create([
            'name' => 'City B',
            'slug' => 'city-b',
            'center_lat' => 10.0,
            'center_lng' => 10.0,
            'radius_m' => 2000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        $this->grant($actor, [
            'view_any_registers',
            'edit_any_registers',
            'delete_any_registers',
        ]);

        $other = User::factory()->create([
            'admin' => false,
            'city_id' => $cityB->id,
        ]);

        $registerOutside = $this->createRegisterAt($cityB, 10.0, 10.0, [
            'user_id' => $other->id,
        ]);

        $this->actingAs($actor)
            ->get(route('registers.show', $registerOutside->id))
            ->assertNotFound();

        $this->actingAs($actor)
            ->get(route('registers.edit', $registerOutside->id))
            ->assertNotFound();

        $this->actingAs($actor)
            ->put(route('registers.update', $registerOutside->id), [
                'title' => 'Updated',
                'content' => 'Updated Content',
                'latitude' => 0.0,
                'longitude' => 0.0,
            ])
            ->assertNotFound();

        $this->actingAs($actor)
            ->get(route('registers.delete.confirm', $registerOutside->id))
            ->assertNotFound();

        $this->actingAs($actor)
            ->post(route('registers.delete', $registerOutside->id), ['confirm' => 'yes'])
            ->assertNotFound();
    }

    public function test_user_cannot_create_register_outside_city(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        $this->grant($actor, ['create_registers']);

        // 0.1 degrees longitude at equator ~ 11km, definitely outside 2km radius.
        $this->actingAs($actor)
            ->post(route('registers.store'), [
                'title' => 'New',
                'content' => 'New Content',
                'latitude' => 0.0,
                'longitude' => 0.1,
            ])
            ->assertSessionHasErrors(['latitude']);
    }

    public function test_map_search_endpoint_never_returns_features_outside_users_city(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $cityB = City::create([
            'name' => 'City B',
            'slug' => 'city-b',
            'center_lat' => 10.0,
            'center_lng' => 10.0,
            'radius_m' => 2000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        $registerInCity = $this->createRegisterAt($cityA, 0.0, 0.0, [
            'user_id' => $actor->id,
        ]);

        $other = User::factory()->create([
            'admin' => false,
            'city_id' => $cityB->id,
        ]);

        $registerOutsideCity = $this->createRegisterAt($cityB, 10.0, 10.0, [
            'user_id' => $other->id,
        ]);

        $response = $this->actingAs($actor)->postJson('/api/registers/search-radius', [
            'bbox' => [-180, -90, 180, 90],
            'limit' => 200,
        ]);

        $response->assertOk();

        $ids = collect($response->json('features') ?? [])
            ->map(fn ($f) => (int) ($f['properties']['id'] ?? 0))
            ->filter()
            ->values();

        $this->assertTrue($ids->contains((int) $registerInCity->id), 'Expected in-city register to be returned');
        $this->assertFalse($ids->contains((int) $registerOutsideCity->id), 'Expected out-of-city register to be filtered out');
    }

    public function test_register_with_only_lat_lng_and_no_location_is_still_restricted_and_visible_inside_city(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 5000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        $this->grant($actor, [
            'view_any_registers',
        ]);

        // Create a register inside the city with latitude/longitude set, but keep `location` NULL.
        $register = Register::create([
            'title_pt' => 'Teste',
            'title_en' => 'Test',
            'content_pt' => 'Conteudo',
            'content_en' => 'Content',
            'category' => 'robo',
            'image' => '/storage/registers/test.jpg',
            'city_id' => $cityA->id,
            'latitude' => 0.0,
            'longitude' => 0.0,
        ]);

        $this->actingAs($actor)
            ->get(route('registers.show', $register->id))
            ->assertOk();

        $response = $this->actingAs($actor)->postJson('/api/registers/search-radius', [
            'bbox' => [-180, -90, 180, 90],
            'limit' => 50,
        ]);

        $response->assertOk();

        $ids = collect($response->json('features') ?? [])
            ->map(fn ($f) => (int) ($f['properties']['id'] ?? 0))
            ->filter()
            ->values();

        $this->assertTrue($ids->contains((int) $register->id), 'Expected register to appear even without location column set');
    }

    public function test_admin_without_city_can_view_and_search_across_cities(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $cityB = City::create([
            'name' => 'City B',
            'slug' => 'city-b',
            'center_lat' => 10.0,
            'center_lng' => 10.0,
            'radius_m' => 2000,
        ]);

        $admin = User::factory()->create([
            'admin' => true,
            'city_id' => null,
        ]);

        $regA = $this->createRegisterAt($cityA, 0.0, 0.0);
        $regB = $this->createRegisterAt($cityB, 10.0, 10.0);

        $this->actingAs($admin)
            ->get(route('registers.show', $regB->id))
            ->assertOk();

        $response = $this->actingAs($admin)->postJson('/api/registers/search-radius', [
            'bbox' => [-180, -90, 180, 90],
            'limit' => 200,
        ]);

        $response->assertOk();

        $ids = collect($response->json('features') ?? [])
            ->map(fn ($f) => (int) ($f['properties']['id'] ?? 0))
            ->filter()
            ->values();

        $this->assertTrue($ids->contains((int) $regA->id));
        $this->assertTrue($ids->contains((int) $regB->id));
    }

    public function test_user_with_view_any_city_registers_permission_can_view_and_search_across_cities_but_writes_still_limited_to_default_city(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $cityB = City::create([
            'name' => 'City B',
            'slug' => 'city-b',
            'center_lat' => 10.0,
            'center_lng' => 10.0,
            'radius_m' => 2000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        $this->grant($actor, ['view_any_city_registers', 'view_any_registers']);

        $regA = $this->createRegisterAt($cityA, 0.0, 0.0);
        $regB = $this->createRegisterAt($cityB, 10.0, 10.0);

        $this->actingAs($actor)
            ->get(route('registers.show', $regB->id))
            ->assertOk();

        $response = $this->actingAs($actor)->postJson('/api/registers/search-radius', [
            'bbox' => [-180, -90, 180, 90],
            'limit' => 200,
        ]);

        $response->assertOk();

        $ids = collect($response->json('features') ?? [])
            ->map(fn ($f) => (int) ($f['properties']['id'] ?? 0))
            ->filter()
            ->values();

        $this->assertTrue($ids->contains((int) $regA->id));
        $this->assertTrue($ids->contains((int) $regB->id));

        // Still cannot edit a register outside their default city.
        $this->grant($actor, ['edit_any_registers', 'delete_any_registers', 'create_own_registers']);

        $this->actingAs($actor)
            ->get(route('registers.edit', $regB->id))
            ->assertNotFound();

        $this->actingAs($actor)
            ->post(route('registers.store'), [
                'title' => 'New',
                'content' => 'New Content',
                'latitude' => 10.0,
                'longitude' => 10.0,
            ])
            ->assertSessionHasErrors(['latitude']);
    }

    public function test_user_with_any_city_registers_write_permissions_can_create_edit_and_delete_across_cities(): void
    {
        $cityA = City::create([
            'name' => 'City A',
            'slug' => 'city-a',
            'center_lat' => 0.0,
            'center_lng' => 0.0,
            'radius_m' => 2000,
        ]);

        $cityB = City::create([
            'name' => 'City B',
            'slug' => 'city-b',
            'center_lat' => 10.0,
            'center_lng' => 10.0,
            'radius_m' => 2000,
        ]);

        $actor = User::factory()->create([
            'admin' => false,
            'city_id' => $cityA->id,
        ]);

        // Base permissions for the page + any-city bypass for reads and writes.
        $this->grant($actor, [
            'view_any_registers',
            'edit_any_registers',
            'delete_any_registers',
            'create_own_registers',

            'view_any_city_registers',
            'create_any_city_registers',
            'edit_any_city_registers',
            'delete_any_city_registers',
        ]);

        // Create in City B (outside default city A)
        $resp = $this->actingAs($actor)
            ->post(route('registers.store'), [
                'title' => 'New',
                'content' => 'New Content',
                'latitude' => 10.0,
                'longitude' => 10.0,
            ]);

        $resp->assertRedirect(route('registers.index'));

        $created = Register::orderBy('id', 'DESC')->first();
        $this->assertNotNull($created);
        $this->assertSame((int) $cityB->id, (int) ($created->city_id ?? 0));

        // Cannot move a register's city by editing its point (city is fixed).
        $ownInA = $this->createRegisterAt($cityA, 0.0, 0.0, ['user_id' => $actor->id]);

        $this->actingAs($actor)
            ->put(route('registers.update', $ownInA->id), [
                'title' => 'Updated',
                'content' => 'Updated Content',
                'latitude' => 10.0,
                'longitude' => 10.0,
            ])
            ->assertSessionHasErrors(['latitude']);

        $ownInA->refresh();
        $this->assertSame((int) $cityA->id, (int) ($ownInA->city_id ?? 0));

        // Delete a register in City B (not in default city).
        $this->actingAs($actor)
            ->get(route('registers.delete.confirm', $created->id))
            ->assertOk();

        $this->actingAs($actor)
            ->post(route('registers.delete', $created->id), ['confirm' => 'yes'])
            ->assertRedirect(route('registers.index'));

        $this->assertDatabaseMissing('registers', ['id' => $created->id]);
    }
}
