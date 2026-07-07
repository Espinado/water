<?php

namespace App\Services;

use App\Models\Building;
use Illuminate\Support\Facades\Session;

class ManagerContext
{
    private const SESSION_BUILDING = 'manager.building_id';

    private const SESSION_YEAR = 'manager.period_year';

    private const SESSION_MONTH = 'manager.period_month';

    public function ensureDefaults(): void
    {
        if ($this->buildingId() === null) {
            $first = Building::query()->orderBy('id')->value('id');
            if ($first !== null) {
                $this->setBuildingId((int) $first);
            }
        }

        $period = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        if ($period !== null) {
            // Пока окно приёма открыто — тот же период, что у жильца (напр. 8 июля → июнь).
            $this->setPeriod($period['year'], $period['month']);
        } elseif (! Session::has(self::SESSION_YEAR) || ! Session::has(self::SESSION_MONTH)) {
            $this->setPeriod((int) now()->year, (int) now()->month);
        }
    }

    public function buildingId(): ?int
    {
        $id = Session::get(self::SESSION_BUILDING);

        return $id !== null ? (int) $id : null;
    }

    public function setBuildingId(int $buildingId): void
    {
        Session::put(self::SESSION_BUILDING, $buildingId);
    }

    public function year(): int
    {
        return (int) Session::get(self::SESSION_YEAR, now()->year);
    }

    public function month(): int
    {
        return (int) Session::get(self::SESSION_MONTH, now()->month);
    }

    /**
     * @return array{year: int, month: int}
     */
    public function period(): array
    {
        return ['year' => $this->year(), 'month' => $this->month()];
    }

    public function setPeriod(int $year, int $month): void
    {
        Session::put(self::SESSION_YEAR, $year);
        Session::put(self::SESSION_MONTH, $month);
    }

    /**
     * @return array{year: int, month: int}|null
     */
    public function actionablePeriod(): ?array
    {
        return app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
    }
}
