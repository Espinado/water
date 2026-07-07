<?php

namespace App\Livewire\Concerns;

use App\Services\ManagerContext;

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
        $context->setPeriod($this->managerPeriodYear(), $this->managerPeriodMonth());
    }

    abstract protected function syncPeriodFromContext(ManagerContext $context): void;

    abstract protected function managerPeriodYear(): int;

    abstract protected function managerPeriodMonth(): int;
}
