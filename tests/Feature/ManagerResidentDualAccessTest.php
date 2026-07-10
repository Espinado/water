<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Manager\HouseholdPanel;
use App\Livewire\Manager\ManagerTeam;
use Tests\TestCase;

class ManagerResidentDualAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_be_linked_to_apartment_by_email_in_household_panel(): void
    {
        $managerUser = User::factory()->manager()->create([
            'email' => 'boss@water.test',
            'apartment_id' => null,
        ]);
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '7']);

        Livewire::actingAs($managerUser)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('openBuilding', $building->id)
            ->call('startCreateResident', $apartment->id)
            ->set('resident_first_name', 'Anna')
            ->set('resident_last_name', 'Boss')
            ->set('resident_email', 'boss@water.test')
            ->call('createResident')
            ->assertHasNoErrors();

        $managerUser->refresh();
        $this->assertSame($apartment->id, $managerUser->apartment_id);
        $this->assertTrue($managerUser->canUseResidentApp());
        $this->assertTrue($managerUser->canUseManagerApp());
    }

    public function test_manager_team_can_assign_apartment(): void
    {
        $admin = User::factory()->manager()->create();
        $colleague = User::factory()->manager()->create([
            'first_name' => 'Colleague',
            'last_name' => 'Manager',
            'apartment_id' => null,
        ]);
        $apartment = Apartment::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManagerTeam::class)
            ->call('startEdit', $colleague->id)
            ->set('edit_apartment_id', $apartment->id)
            ->call('saveManager')
            ->assertHasNoErrors();

        $colleague->refresh();
        $this->assertSame($apartment->id, $colleague->apartment_id);
    }
}
