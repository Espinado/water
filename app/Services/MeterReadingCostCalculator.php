<?php

namespace App\Services;

use App\Models\MeterReading;

class MeterReadingCostCalculator
{
    public function __construct(
        protected ServiceRateResolver $rateResolver,
    ) {}

    /**
     * @return array{
     *     cold_consumption_m3: ?string,
     *     hot_consumption_m3: ?string,
     *     cold_price_per_m3: ?string,
     *     hot_price_per_m3: ?string,
     *     cold_cost: ?string,
     *     hot_cost: ?string,
     *     total_water_cost: ?string,
     * }
     */
    public function calculate(MeterReading $reading): array
    {
        $reading->loadMissing('apartment');
        $buildingId = $reading->apartment?->building_id;
        $previous = $this->previousReading($reading);

        $coldPrice = $this->rateResolver->priceForBuilding($buildingId, 'water_cold');
        $hotPrice = $this->rateResolver->priceForBuilding($buildingId, 'water_hot');

        if ($coldPrice === null || $hotPrice === null || $previous === null) {
            return $this->emptyCosts();
        }

        $coldConsumption = (float) $reading->cold_m3 - (float) $previous->cold_m3;
        $hotConsumption = (float) $reading->hot_m3 - (float) $previous->hot_m3;

        if ($coldConsumption < 0 || $hotConsumption < 0) {
            return $this->emptyCosts();
        }

        $coldCost = round($coldConsumption * $coldPrice, 2);
        $hotCost = round($hotConsumption * $hotPrice, 2);

        return [
            'cold_consumption_m3' => $this->formatDecimal($coldConsumption, 3),
            'hot_consumption_m3' => $this->formatDecimal($hotConsumption, 3),
            'cold_price_per_m3' => $this->formatDecimal($coldPrice, 2),
            'hot_price_per_m3' => $this->formatDecimal($hotPrice, 2),
            'cold_cost' => $this->formatDecimal($coldCost, 2),
            'hot_cost' => $this->formatDecimal($hotCost, 2),
            'total_water_cost' => $this->formatDecimal($coldCost + $hotCost, 2),
        ];
    }

    public function apply(MeterReading $reading): MeterReading
    {
        $reading->update($this->calculate($reading));

        return $reading->fresh();
    }

    public function recalculateNextPeriod(MeterReading $reading): void
    {
        $next = $this->nextReading($reading);

        if ($next !== null) {
            $this->apply($next);
        }
    }

    public function previousReading(MeterReading $reading): ?MeterReading
    {
        [$year, $month] = $this->previousPeriod($reading->year, $reading->month);

        return MeterReading::query()
            ->where('apartment_id', $reading->apartment_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function nextReading(MeterReading $reading): ?MeterReading
    {
        [$year, $month] = $this->nextPeriod($reading->year, $reading->month);

        return MeterReading::query()
            ->where('apartment_id', $reading->apartment_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    /**
     * @return array{year: int, month: int}
     */
    public function previousPeriod(int $year, int $month): array
    {
        $month--;
        if ($month < 1) {
            $month = 12;
            $year--;
        }

        return ['year' => $year, 'month' => $month];
    }

    /**
     * @return array{year: int, month: int}
     */
    public function nextPeriod(int $year, int $month): array
    {
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        return ['year' => $year, 'month' => $month];
    }

    /**
     * @return array{
     *     cold_consumption_m3: null,
     *     hot_consumption_m3: null,
     *     cold_price_per_m3: null,
     *     hot_price_per_m3: null,
     *     cold_cost: null,
     *     hot_cost: null,
     *     total_water_cost: null,
     * }
     */
    protected function emptyCosts(): array
    {
        return [
            'cold_consumption_m3' => null,
            'hot_consumption_m3' => null,
            'cold_price_per_m3' => null,
            'hot_price_per_m3' => null,
            'cold_cost' => null,
            'hot_cost' => null,
            'total_water_cost' => null,
        ];
    }

    protected function formatDecimal(float $value, int $scale): string
    {
        return number_format($value, $scale, '.', '');
    }
}
