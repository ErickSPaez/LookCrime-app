<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistersSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_radius_endpoint_includes_image_and_url_for_map_popups(): void
    {
        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
            'center_lat' => 48.8566,
            'center_lng' => 2.3522,
            'radius_m' => 500000,
        ]);

        $user = User::create([
            'name' => 'Test User',
            'nickname' => '',
            'institution' => '',
            'email' => 'test@example.com',
            'password' => bcrypt('secret123'),
            'admin' => false,
            'banned' => false,
            'city_id' => $city->id,
        ]);

        $register = Register::create([
            'title_pt' => 'Teste',
            'title_en' => 'Test',
            'content_pt' => 'Conteudo',
            'content_en' => 'Content',
            'category' => 'robo',
            'image' => '/storage/registers/test.jpg',
            'city_id' => $city->id,
        ]);

        // Ensure PostGIS location is set so it can match bbox queries
        DB::update(
            'UPDATE registers SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [2.3522, 48.8566, $register->id] // lng, lat
        );

        $response = $this->actingAs($user)->postJson('/api/registers/search-radius', [
            'bbox' => [-180, -90, 180, 90],
            'limit' => 50,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'type',
                'features' => [
                    ['type', 'geometry', 'properties' => ['id', 'title', 'category', 'created_at', 'image', 'url']],
                ],
            ]);

        $json = $response->json();
        $feature = collect($json['features'] ?? [])->first(function ($f) use ($register) {
            return (int) (($f['properties']['id'] ?? 0)) === (int) $register->id;
        });

        $this->assertNotNull($feature, 'Expected created register to appear in search results');
        $this->assertNotEmpty($feature['properties']['image'] ?? null);
        $this->assertNotEmpty($feature['properties']['url'] ?? null);
        $this->assertStringContainsString('/registers/' . $register->id, $feature['properties']['url']);
    }
}
