<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'number' => (string) fake()->numberBetween(1, 200),
        ];
    }
}
