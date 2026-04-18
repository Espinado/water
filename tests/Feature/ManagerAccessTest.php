<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_manager_panel(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get('/manager');

        $response->assertOk();
    }

    public function test_manager_can_open_apartments_table(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get('/manager/apartments');

        $response->assertOk();
    }

    public function test_resident_cannot_open_manager_panel(): void
    {
        $resident = User::factory()->create([
            'apartment_id' => null,
        ]);

        $response = $this->actingAs($resident)->get('/manager');

        $response->assertForbidden();
    }

    public function test_resident_cannot_open_apartments_table(): void
    {
        $resident = User::factory()->create([
            'apartment_id' => null,
        ]);

        $response = $this->actingAs($resident)->get('/manager/apartments');

        $response->assertForbidden();
    }
}
