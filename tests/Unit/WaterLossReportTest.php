<?php

namespace Tests\Unit;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Models\ServiceProvider;
use App\Models\SupplierInvoice;
use App\Services\WaterConsumptionAggregator;
use App\Services\WaterLossReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregates_consumption_for_single_building(): void
    {
        $buildingA = Building::factory()->create(['name' => 'Дом A']);
        $buildingB = Building::factory()->create(['name' => 'Дом B']);
        $aptA = Apartment::factory()->for($buildingA)->create();
        Apartment::factory()->for($buildingB)->create();

        MeterReading::query()->create([
            'apartment_id' => $aptA->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 10.000,
            'hot_m3' => 5.000,
            'cold_consumption_m3' => 1.000,
            'hot_consumption_m3' => 0.500,
            'entered_by_manager' => true,
        ]);

        $result = app(WaterConsumptionAggregator::class)->aggregateForPeriod(2026, 6, $buildingA->id);

        $this->assertSame(1, $result['total_apartments']);
        $this->assertSame(0, $result['missing_apartments']);
        $this->assertSame('1.000', $result['cold_m3']);
    }

    public function test_aggregates_consumption_across_all_buildings(): void
    {
        $buildingA = Building::factory()->create(['name' => 'Дом A']);
        $buildingB = Building::factory()->create(['name' => 'Дом B']);
        $aptA = Apartment::factory()->for($buildingA)->create();
        $aptB = Apartment::factory()->for($buildingB)->create();

        MeterReading::query()->create([
            'apartment_id' => $aptA->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 100.000,
            'hot_m3' => 50.000,
            'cold_consumption_m3' => 1.200,
            'hot_consumption_m3' => 0.800,
            'cold_cost' => 5.46,
            'hot_cost' => 3.64,
            'entered_by_manager' => true,
        ]);

        MeterReading::query()->create([
            'apartment_id' => $aptB->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 80.000,
            'hot_m3' => 40.000,
            'cold_consumption_m3' => 0.500,
            'hot_consumption_m3' => 0.300,
            'cold_cost' => 2.28,
            'hot_cost' => 1.37,
            'entered_by_manager' => true,
        ]);

        $result = app(WaterConsumptionAggregator::class)->aggregateForPeriod(2026, 6);

        $this->assertSame(2, $result['total_apartments']);
        $this->assertSame(2, $result['submitted_apartments']);
        $this->assertSame(0, $result['missing_apartments']);
        $this->assertSame('1.700', $result['cold_m3']);
        $this->assertSame('1.100', $result['hot_m3']);
        $this->assertSame('7.74', $result['cold_amount']);
        $this->assertSame('5.01', $result['hot_amount']);
    }

    public function test_calculates_loss_in_m3_and_eur(): void
    {
        $provider = ServiceProvider::factory()->withWaterRates()->create();
        $building = Building::factory()->create();
        $apartment = Apartment::factory()->for($building)->create();

        MeterReading::query()->create([
            'apartment_id' => $apartment->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 10.000,
            'hot_m3' => 5.000,
            'cold_consumption_m3' => 2.000,
            'hot_consumption_m3' => 1.000,
            'cold_cost' => 9.10,
            'hot_cost' => 4.55,
            'entered_by_manager' => true,
        ]);

        $invoice = SupplierInvoice::query()->create([
            'service_provider_id' => $provider->id,
            'year' => 2026,
            'month' => 6,
            'cold_m3' => 2.500,
            'cold_amount' => 11.38,
            'hot_m3' => 1.200,
            'hot_amount' => 5.46,
        ]);

        $report = app(WaterLossReport::class)->forPeriod(2026, 6, $invoice);

        $this->assertTrue($report['has_invoice']);
        $this->assertSame('0.500', $report['loss']['cold_m3']);
        $this->assertSame('0.200', $report['loss']['hot_m3']);
        $this->assertSame('2.28', $report['loss']['cold_amount']);
        $this->assertSame('0.91', $report['loss']['hot_amount']);
    }

    public function test_flags_missing_apartments(): void
    {
        $building = Building::factory()->create();
        Apartment::factory()->for($building)->count(3)->create();

        $result = app(WaterConsumptionAggregator::class)->aggregateForPeriod(2026, 6);

        $this->assertSame(3, $result['total_apartments']);
        $this->assertSame(3, $result['missing_apartments']);
    }
}
