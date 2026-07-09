<?php

namespace Tests\Unit;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Models\ServiceProvider;
use App\Services\MeterReadingCostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_water_costs_from_supplier_tariffs(): void
    {
        ServiceProvider::factory()->withWaterRates(4.55, 4.55)->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 5,
            'cold_m3' => 100.000,
            'hot_m3' => 50.000,
            'entered_by_manager' => true,
        ]);

        $current = MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 100.820,
            'hot_m3' => 50.350,
            'entered_by_manager' => true,
        ]);

        $calculator = app(MeterReadingCostCalculator::class);
        $calculator->apply($current);

        $current->refresh();

        $this->assertSame('0.820', (string) $current->cold_consumption_m3);
        $this->assertSame('0.350', (string) $current->hot_consumption_m3);
        $this->assertSame('3.73', (string) $current->cold_cost);
        $this->assertSame('1.59', (string) $current->hot_cost);
        $this->assertSame('5.32', (string) $current->total_water_cost);
        $this->assertSame('4.55', (string) $current->cold_price_per_m3);
        $this->assertSame('4.55', (string) $current->hot_price_per_m3);
    }

    public function test_leaves_costs_empty_without_previous_reading(): void
    {
        ServiceProvider::factory()->withWaterRates()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        $reading = MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 10.000,
            'hot_m3' => 5.000,
            'entered_by_manager' => true,
        ]);

        app(MeterReadingCostCalculator::class)->apply($reading);
        $reading->refresh();

        $this->assertNull($reading->cold_consumption_m3);
        $this->assertNull($reading->total_water_cost);
    }

    public function test_recalculates_next_period_when_previous_reading_changes(): void
    {
        ServiceProvider::factory()->withWaterRates(2.00, 3.00)->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        $may = MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 5,
            'cold_m3' => 10.000,
            'hot_m3' => 5.000,
            'entered_by_manager' => true,
        ]);

        $june = MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 12.000,
            'hot_m3' => 7.000,
            'entered_by_manager' => true,
        ]);

        $calculator = app(MeterReadingCostCalculator::class);
        $calculator->apply($june);
        $june->refresh();
        $this->assertSame('4.00', (string) $june->cold_cost);

        $may->update(['cold_m3' => 11.000]);
        $calculator->apply($may);
        $calculator->recalculateNextPeriod($may);

        $june->refresh();
        $this->assertSame('2.00', (string) $june->cold_cost);
    }
}
