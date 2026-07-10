<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_manager_dashboard(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get($this->managerUrl('/dashboard'))
            ->assertOk();
    }

    public function test_manager_can_open_setup_panel(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get($this->managerUrl('/setup'))
            ->assertOk();
    }

    public function test_manager_apartments_route_redirects_to_setup(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get($this->managerUrl('/apartments?filter=debt'))
            ->assertRedirect($this->managerUrl('/setup?filter=debt'));
    }

    public function test_resident_cannot_open_manager_panel(): void
    {
        $resident = User::factory()->create([
            'apartment_id' => null,
        ]);

        $this->actingAs($resident)
            ->get($this->managerUrl('/dashboard'))
            ->assertRedirect($this->managerUrl('/login'));

        $this->assertGuest();
    }

    public function test_resident_cannot_open_apartments_route(): void
    {
        $resident = User::factory()->create([
            'apartment_id' => null,
        ]);

        $this->actingAs($resident)
            ->get($this->managerUrl('/apartments'))
            ->assertRedirect($this->managerUrl('/login'));

        $this->assertGuest();
    }

    public function test_legacy_manager_path_redirects_to_manager_subdomain(): void
    {
        $this->get($this->residentUrl('/manager/setup'))
            ->assertRedirect($this->managerUrl('/setup'));
    }

    public function test_manager_can_open_profile_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get($this->managerUrl('/profile'))
            ->assertOk()
            ->assertSee(__('Профиль'))
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form');
    }
}
