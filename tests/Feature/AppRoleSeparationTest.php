<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_without_apartment_cannot_use_resident_app(): void
    {
        $manager = User::factory()->manager()->create([
            'email' => 'manager@water.test',
            'apartment_id' => null,
        ]);

        $this->actingAs($manager)
            ->get($this->residentUrl('/dashboard'))
            ->assertRedirect($this->residentUrl('/login'));

        $this->assertGuest();
    }

    public function test_resident_cannot_use_manager_app(): void
    {
        $resident = User::factory()->create(['apartment_id' => null]);

        $this->actingAs($resident)
            ->get($this->managerUrl('/dashboard'))
            ->assertRedirect($this->managerUrl('/login'));

        $this->assertGuest();
    }

    public function test_manager_with_apartment_can_use_resident_app(): void
    {
        $manager = User::factory()->manager()->create();
        $apartment = \App\Models\Apartment::factory()->create();
        $manager->update(['apartment_id' => $apartment->id]);

        $this->actingAs($manager)
            ->get($this->residentUrl('/dashboard'))
            ->assertOk();
    }
}
