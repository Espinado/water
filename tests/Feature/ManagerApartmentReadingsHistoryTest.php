<?php

namespace Tests\Feature;

use App\Livewire\Manager\ApartmentReadingsHistory;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerApartmentReadingsHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_latest_reading_period(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K19']);
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 3,
            'cold_m3' => 103,
            'hot_m3' => 53,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 4,
            'cold_m3' => 110,
            'hot_m3' => 60,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->assertSet('filterYear', 2026)
            ->assertSet('filterMonth', 4)
            ->assertSee('110.000');
    }

    public function test_filters_by_selected_year_and_month(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create(['name' => 'K19']);
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 3,
            'cold_m3' => 103,
            'hot_m3' => 53,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2025,
            'month' => 4,
            'cold_m3' => 104,
            'hot_m3' => 54,
            'recorded_by_user_id' => $manager->id,
            'entered_by_manager' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('filterYear', 2025)
            ->set('filterMonth', 3)
            ->assertSee('103.000')
            ->assertDontSee('104.000');

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('filterYear', 2024)
            ->assertSet('filterMonth', 1)
            ->assertSee(__('Показаний за выбранный период нет.'));
    }

    public function test_manager_can_enter_reading_manually(): void
    {
        $manager = User::factory()->manager()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create(['number' => '6']);

        Livewire::actingAs($manager)
            ->test(ApartmentReadingsHistory::class, ['apartment' => $apartment])
            ->set('entryYear', 2026)
            ->set('entryMonth', 5)
            ->set('entry_cold', '123.5')
            ->set('entry_hot', '77.25')
            ->call('saveEntry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meter_readings', [
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 5,
            'entered_by_manager' => true,
        ]);
    }
}
