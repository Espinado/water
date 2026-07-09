<?php

namespace Tests\Feature;

use App\Livewire\Manager\MeterReadings;
use App\Livewire\Manager\ServiceProviders;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Services\ManagerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceProviderAndCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_service_provider_with_rates(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ServiceProviders::class)
            ->set('new_code', 'JUR_UDENS')
            ->set('new_name', 'Jūrmalas ūdens')
            ->call('createProvider')
            ->assertHasNoErrors();

        $provider = ServiceProvider::query()->where('code', 'JUR_UDENS')->first();
        $this->assertNotNull($provider);
        $this->assertSame('Jūrmalas ūdens', $provider->name);

        Livewire::actingAs($manager)
            ->test(ServiceProviders::class)
            ->call('startEdit', $provider->id)
            ->set('new_rate_service', 'water_cold')
            ->set('new_rate_price', '4.55')
            ->call('addRate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('provider_service_rates', [
            'service_provider_id' => $provider->id,
            'service_code' => 'water_cold',
            'price' => 4.55,
        ]);
    }

    public function test_manager_can_add_second_rate_via_save(): void
    {
        $manager = User::factory()->manager()->create();
        $provider = ServiceProvider::factory()->create([
            'code' => 'JUR_UDENS',
            'name' => 'Jūrmalas ūdens',
        ]);
        $provider->rates()->create([
            'service_code' => 'water_cold',
            'price' => 4.55,
        ]);

        Livewire::actingAs($manager)
            ->test(ServiceProviders::class)
            ->call('startEdit', $provider->id)
            ->set('new_rate_service', 'water_hot')
            ->set('new_rate_price', '4.55')
            ->call('saveProvider')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('provider_service_rates', [
            'service_provider_id' => $provider->id,
            'service_code' => 'water_hot',
            'price' => 4.55,
        ]);
        $this->assertDatabaseCount('provider_service_rates', 2);
    }

    public function test_saving_readings_calculates_water_cost_automatically(): void
    {
        $manager = User::factory()->manager()->create();
        ServiceProvider::factory()->withWaterRates(4.55, 4.55)->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '5']);

        \App\Models\MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 5,
            'cold_m3' => 100.000,
            'hot_m3' => 50.000,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        app(ManagerContext::class)->setBuildingId($building->id);
        app(ManagerContext::class)->setPeriod(2026, 6);

        Livewire::actingAs($manager)
            ->test(MeterReadings::class)
            ->call('startEditApartment', $apartment->id)
            ->set('edit_cold', '100.820')
            ->set('edit_hot', '50.350')
            ->call('saveEditingApartment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meter_readings', [
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_cost' => 3.73,
            'hot_cost' => 1.59,
            'total_water_cost' => 5.32,
        ]);
    }

    public function test_resident_cannot_access_suppliers_page(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->get(route('manager.suppliers'))
            ->assertForbidden();
    }
}
