<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        User::query()->create([
            'name' => 'Управляющий',
            'email' => 'manager@water.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::Manager,
            'apartment_id' => null,
        ]);
    }
}
