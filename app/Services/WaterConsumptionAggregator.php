<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\MeterReading;

class WaterConsumptionAggregator
{
    /**
     * @return array{
     *     total_apartments: int,
     *     submitted_apartments: int,
     *     calculated_apartments: int,
     *     missing_apartments: int,
     *     incomplete_apartments: int,
     *     cold_m3: string,
     *     hot_m3: string,
     *     cold_amount: string,
     *     hot_amount: string,
     * }
     */
    public function aggregateForPeriod(int $year, int $month, ?int $buildingId = null): array
    {
        $apartmentsQuery = Apartment::query();

        if ($buildingId !== null) {
            $apartmentsQuery->where('building_id', $buildingId);
        }

        $apartmentIds = $apartmentsQuery->pluck('id');
        $totalApartments = $apartmentIds->count();

        $readings = MeterReading::query()
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('apartment_id', $apartmentIds)
            ->get();

        $submittedApartments = $readings->count();
        $calculated = $readings->filter(
            fn (MeterReading $reading) => $reading->cold_consumption_m3 !== null
                && $reading->hot_consumption_m3 !== null
        );

        $coldM3 = $calculated->sum(fn (MeterReading $reading) => (float) $reading->cold_consumption_m3);
        $hotM3 = $calculated->sum(fn (MeterReading $reading) => (float) $reading->hot_consumption_m3);
        $coldAmount = $calculated->sum(fn (MeterReading $reading) => (float) ($reading->cold_cost ?? 0));
        $hotAmount = $calculated->sum(fn (MeterReading $reading) => (float) ($reading->hot_cost ?? 0));

        return [
            'total_apartments' => $totalApartments,
            'submitted_apartments' => $submittedApartments,
            'calculated_apartments' => $calculated->count(),
            'missing_apartments' => max(0, $totalApartments - $submittedApartments),
            'incomplete_apartments' => max(0, $submittedApartments - $calculated->count()),
            'cold_m3' => $this->format($coldM3, 3),
            'hot_m3' => $this->format($hotM3, 3),
            'cold_amount' => $this->format($coldAmount, 2),
            'hot_amount' => $this->format($hotAmount, 2),
        ];
    }

    protected function format(float $value, int $scale): string
    {
        return number_format($value, $scale, '.', '');
    }
}
