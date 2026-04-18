<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $building = Building::query()->create([
            'name' => 'Пилотный дом',
            'address' => null,
        ]);

        foreach (['1', '2', '3'] as $number) {
            Apartment::query()->create([
                'building_id' => $building->id,
                'number' => $number,
            ]);
        }

        $this->call(DevManagerSeeder::class);
    }
}
