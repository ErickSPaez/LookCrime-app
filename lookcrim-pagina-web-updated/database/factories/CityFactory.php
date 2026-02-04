<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'City ' . Str::upper(Str::random(6));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'center_lat' => $this->faker->latitude(-89, 89),
            'center_lng' => $this->faker->longitude(-179, 179),
            'radius_m' => $this->faker->numberBetween(500, 50000),
        ];
    }
}
