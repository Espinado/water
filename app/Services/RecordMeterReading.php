<?php

namespace App\Services;

use App\Models\MeterReading;

class RecordMeterReading
{
    public function __construct(
        protected MeterReadingCostCalculator $calculator,
    ) {}

    /**
     * @param  array{apartment_id: int, year: int, month: int}  $keys
     * @param  array<string, mixed>  $values
     */
    public function upsert(array $keys, array $values): MeterReading
    {
        $reading = MeterReading::query()->updateOrCreate($keys, $values);

        $this->calculator->apply($reading);
        $this->calculator->recalculateNextPeriod($reading);

        return $reading->fresh();
    }

    public function update(MeterReading $reading, array $values): MeterReading
    {
        $reading->update($values);

        $this->calculator->apply($reading);
        $this->calculator->recalculateNextPeriod($reading);

        return $reading->fresh();
    }
}
