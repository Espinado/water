<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevManagerSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'manager@water.test'],
            [
                'name' => 'Управляющий',
                'email_verified_at' => now(),
                'password' => Hash::make('test12345'),
                'role' => UserRole::Manager,
                'apartment_id' => null,
            ],
        );
    }
}
