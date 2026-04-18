<?php

namespace App\Services;

use Carbon\Carbon;

class MeterSubmissionWindow
{
    public function opensAt(int $year, int $month): Carbon
    {
        $day = (int) config('water.submission_opens_day');

        return Carbon::create($year, $month, $day, 0, 0, 0, config('app.timezone'));
    }

    public function closesAt(int $year, int $month): Carbon
    {
        $closeDay = (int) config('water.submission_closes_day');

        return Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone'))
            ->addMonth()
            ->day($closeDay)
            ->endOfDay();
    }

    public function isOpenForResident(int $year, int $month, ?Carbon $at = null): bool
    {
        if (config('water.submission_window_bypass')) {
            return true;
        }

        $at = $at ?? now();

        return $at->between($this->opensAt($year, $month), $this->closesAt($year, $month));
    }

    /**
     * Расчётный месяц, для которого жилец может передать показания «сейчас», или null (11–24 число).
     *
     * @return array{year: int, month: int}|null
     */
    public function residentActionablePeriodAt(?Carbon $at = null): ?array
    {
        $at = $at ?? now();

        if (config('water.submission_window_bypass')) {
            return ['year' => $at->year, 'month' => $at->month];
        }

        $openDay = (int) config('water.submission_opens_day');
        $closeDay = (int) config('water.submission_closes_day');

        if ($at->day >= $openDay) {
            return ['year' => $at->year, 'month' => $at->month];
        }

        if ($at->day <= $closeDay) {
            $prev = $at->copy()->subMonthNoOverflow();

            return ['year' => $prev->year, 'month' => $prev->month];
        }

        return null;
    }
}
