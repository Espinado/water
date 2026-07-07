<?php

namespace Tests\Feature;

use App\Livewire\Manager\ApartmentTable;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerEditResidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_edit_resident_in_apartment_table(): void
    {
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '19']);
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create([
            'apartment_id' => $apartment->id,
            'first_name' => 'Julja',
            'last_name' => 'Junusova',
            'name' => 'Junusova Julja',
            'email' => 'julja@example.com',
            'phone' => '+37120000001',
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentTable::class)
            ->set('building_id', $building->id)
            ->call('startEditResident', $resident->id)
            ->assertSet('edit_first_name', 'Julja')
            ->assertSet('edit_last_name', 'Junusova')
            ->assertSet('edit_email', 'julja@example.com')
            ->set('edit_first_name', 'Julia')
            ->set('edit_last_name', 'Yunusova')
            ->set('edit_phone', '+37121111111')
            ->set('edit_email', 'julia@example.com')
            ->call('saveResident')
            ->assertHasNoErrors()
            ->assertSet('editingResidentId', null);

        $resident->refresh();

        $this->assertSame('Julia', $resident->first_name);
        $this->assertSame('Yunusova', $resident->last_name);
        $this->assertSame('Yunusova Julia', $resident->name);
        $this->assertSame('+37121111111', $resident->phone);
        $this->assertSame('julia@example.com', $resident->email);
    }

    public function test_manager_cannot_edit_resident_from_another_building(): void
    {
        $buildingA = Building::factory()->create();
        $buildingB = Building::factory()->create();
        $apartmentB = Apartment::factory()->for($buildingB)->create();
        $manager = User::factory()->manager()->create();
        $resident = User::factory()->create(['apartment_id' => $apartmentB->id]);

        Livewire::actingAs($manager)
            ->test(ApartmentTable::class)
            ->set('building_id', $buildingA->id)
            ->call('startEditResident', $resident->id)
            ->assertForbidden();
    }
}
