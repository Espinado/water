<?php

namespace Tests\Feature;

use App\Livewire\Manager\HouseholdPanel;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerHouseholdCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_update_and_delete_building(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K16', 'address' => 'Old']);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('startEditBuilding', $building->id)
            ->set('edit_building_name', 'K16 New')
            ->set('edit_building_address', 'New address')
            ->call('saveBuilding')
            ->assertHasNoErrors();

        $building->refresh();
        $this->assertSame('K16 New', $building->name);
        $this->assertSame('New address', $building->address);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('deleteBuilding', $building->id);

        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    public function test_manager_can_create_apartment_via_modal(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('openBuilding', $building->id)
            ->call('startCreateApartment')
            ->assertSet('creatingApartment', true)
            ->set('new_apartment_number', '12')
            ->set('new_apartment_area_m2', '45.84')
            ->set('resident_first_name', 'Anna')
            ->set('resident_last_name', 'Bērziņa')
            ->set('resident_phone', '+37120000001')
            ->set('resident_email', 'anna.new@example.com')
            ->call('createApartment')
            ->assertHasNoErrors()
            ->assertSet('creatingApartment', false);

        $this->assertDatabaseHas('apartments', [
            'building_id' => $building->id,
            'number' => '12',
            'area_m2' => 45.84,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'anna.new@example.com',
            'first_name' => 'Anna',
            'last_name' => 'Bērziņa',
            'phone' => '+37120000001',
        ]);
    }

    public function test_manager_can_update_and_delete_apartment_without_resident(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '5']);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('startEditApartment', $apartment->id)
            ->set('edit_apartment_number', '5A')
            ->set('edit_apartment_area_m2', '45.84')
            ->call('saveApartment')
            ->assertHasNoErrors();

        $apartment->refresh();
        $this->assertSame('5A', $apartment->number);
        $this->assertSame('45.84', (string) $apartment->area_m2);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('deleteApartment', $apartment->id);

        $this->assertDatabaseMissing('apartments', ['id' => $apartment->id]);
    }

    public function test_manager_cannot_delete_apartment_with_resident(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();
        User::factory()->create(['apartment_id' => $apartment->id]);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('deleteApartment', $apartment->id);

        $this->assertDatabaseHas('apartments', ['id' => $apartment->id]);
    }

    public function test_manager_can_create_edit_and_delete_resident(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '12']);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('startCreateResident', $apartment->id)
            ->set('resident_first_name', 'Anna')
            ->set('resident_last_name', 'Bērziņa')
            ->set('resident_email', 'anna@example.com')
            ->call('createResident')
            ->assertHasNoErrors();

        $resident = User::query()->where('email', 'anna@example.com')->first();
        $this->assertNotNull($resident);
        $this->assertSame($apartment->id, $resident->apartment_id);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('startEditResident', $resident->id)
            ->set('edit_apartment_area_m2', '45.84')
            ->set('edit_resident_first_name', 'Anete')
            ->call('saveResident')
            ->assertHasNoErrors();

        $resident->refresh();
        $this->assertSame('Anete', $resident->first_name);
        $apartment->refresh();
        $this->assertSame('45.84', (string) $apartment->area_m2);

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->set('building_id', $building->id)
            ->call('deleteResident', $resident->id);

        $this->assertDatabaseMissing('users', ['id' => $resident->id]);
    }

    public function test_apartments_inside_building_support_search_sort_and_pagination(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'Search House']);

        $match = Apartment::factory()->for($building)->create(['number' => '101']);
        User::factory()->create([
            'apartment_id' => $match->id,
            'first_name' => 'Ieva',
            'last_name' => 'Kalniņa',
            'name' => 'Kalniņa Ieva',
            'email' => 'ieva.unique@example.com',
            'phone' => '+37120000001',
        ]);

        Apartment::factory()->for($building)->create(['number' => '202']);

        for ($i = 1; $i <= 16; $i++) {
            Apartment::factory()->for($building)->create(['number' => (string) (300 + $i)]);
        }

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('openBuilding', $building->id)
            ->assertSet('inBuilding', true)
            ->set('search', 'ieva.unique@example.com')
            ->assertSee('101')
            ->assertDontSee('202');

        Livewire::actingAs($manager)
            ->test(HouseholdPanel::class)
            ->call('openBuilding', $building->id)
            ->set('search', '')
            ->call('sortBy', 'number')
            ->tap(function ($component) {
                $this->assertSame(15, $component->instance()->apartments->perPage());
                $this->assertTrue($component->instance()->apartments->hasMorePages());
            });
    }
}
