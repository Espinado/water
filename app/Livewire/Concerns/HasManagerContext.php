<?php

namespace App\Livewire\Concerns;

use App\Services\ManagerContext;
use App\Services\MeterSubmissionWindow;
use Carbon\Carbon;
use Livewire\Attributes\Computed;

trait HasManagerContext
{
    protected function bootManagerContext(ManagerContext $context): void
    {
        $context->ensureDefaults();
    }

    protected function loadManagerContext(ManagerContext $context): void
    {
        $context->ensureDefaults();
        $this->building_id = $context->buildingId();
        $this->syncPeriodFromContext($context);
    }

    protected function persistManagerContext(ManagerContext $context): void
    {
        if ($this->building_id) {
            $context->setBuildingId((int) $this->building_id);
        }

        $actionable = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        if ($actionable !== null) {
            $context->setPeriod($actionable['year'], $actionable['month']);
        } else {
            $context->setPeriod($this->managerPeriodYear(), $this->managerPeriodMonth());
        }

        $this->syncPeriodFromContext($context);
    }

    #[Computed]
    public function managerLockedPeriodLabel(): ?string
    {
        $period = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        if ($period === null) {
            return null;
        }

        return Carbon::create($period['year'], $period['month'], 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    abstract protected function syncPeriodFromContext(ManagerContext $context): void;

    abstract protected function managerPeriodYear(): int;

    abstract protected function managerPeriodMonth(): int;
}
