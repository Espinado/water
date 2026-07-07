<?php

namespace App\Services;

use App\Models\Apartment;
use Illuminate\Support\Facades\Cache;

class MeterReadingSubmissionNotifier
{
    /**
     * @return array{apartment_id: int, apartment_number: string, building_id: int, year: int, month: int, submitted_at: int}
     */
    public function notify(Apartment $apartment, int $year, int $month, bool $enteredByManager): void
    {
        if ($enteredByManager) {
            return;
        }

        $key = $this->cacheKey((int) $apartment->building_id);
        $lockKey = $key.':lock';

        Cache::lock($lockKey, 5)->block(3, function () use ($key, $apartment, $year, $month): void {
            $events = Cache::get($key, []);
            $events[] = [
                'apartment_id' => (int) $apartment->id,
                'apartment_number' => (string) $apartment->number,
                'building_id' => (int) $apartment->building_id,
                'year' => $year,
                'month' => $month,
                'submitted_at' => now()->timestamp,
            ];
            Cache::put($key, $events, now()->addHours(6));
        });
    }

    /**
     * @return list<array{apartment_id: int, apartment_number: string, building_id: int, year: int, month: int, submitted_at: int}>
     */
    public function pullForBuildingPeriod(int $buildingId, int $year, int $month): array
    {
        $key = $this->cacheKey($buildingId);
        $lockKey = $key.':lock';

        return Cache::lock($lockKey, 5)->block(3, function () use ($key, $year, $month): array {
            $events = Cache::get($key, []);
            if (! is_array($events) || $events === []) {
                return [];
            }

            $matched = [];
            $remaining = [];

            foreach ($events as $event) {
                if ((int) ($event['year'] ?? 0) === $year && (int) ($event['month'] ?? 0) === $month) {
                    $matched[] = $event;
                } else {
                    $remaining[] = $event;
                }
            }

            if ($remaining === []) {
                Cache::forget($key);
            } else {
                Cache::put($key, $remaining, now()->addHours(6));
            }

            return $matched;
        });
    }

    protected function cacheKey(int $buildingId): string
    {
        return 'meter_submission_notifications:building:'.$buildingId;
    }
}
