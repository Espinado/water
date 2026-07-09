<?php

namespace App\Services;

use App\Models\BuildingServiceProvider;
use App\Models\ProviderServiceRate;

class ServiceRateResolver
{
    public function priceForBuilding(?int $buildingId, string $serviceCode): ?float
    {
        if ($buildingId !== null) {
            $assignment = BuildingServiceProvider::query()
                ->where('building_id', $buildingId)
                ->where('service_code', $serviceCode)
                ->with('provider.rates')
                ->first();

            if ($assignment !== null) {
                $rate = $assignment->provider?->rateFor($serviceCode);

                if ($rate !== null) {
                    return (float) $rate->price;
                }
            }
        }

        $fallback = ProviderServiceRate::query()
            ->where('service_code', $serviceCode)
            ->orderBy('id')
            ->first();

        return $fallback !== null ? (float) $fallback->price : null;
    }
}
