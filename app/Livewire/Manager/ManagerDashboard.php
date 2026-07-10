<?php

namespace App\Livewire\Manager;

use App\Livewire\Concerns\HasManagerContext;
use App\Models\Building;
use App\Services\ManagerBuildingStats;
use App\Services\ManagerContext;
use App\Services\MeterReadingSubmissionNotifier;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class ManagerDashboard extends Component
{
    use HasManagerContext;

    public ?int $building_id = null;

    public int $statusYear = 0;

    public int $statusMonth = 0;

    public function mount(ManagerContext $context): void
    {
        $this->loadManagerContext($context);
    }

    protected function syncPeriodFromContext(ManagerContext $context): void
    {
        $this->statusYear = $context->year();
        $this->statusMonth = $context->month();
    }

    protected function managerPeriodYear(): int
    {
        return $this->statusYear;
    }

    protected function managerPeriodMonth(): int
    {
        return $this->statusMonth;
    }

    public function updatedBuildingId(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
    }

    public function updatedStatusYear(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
    }

    public function updatedStatusMonth(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
    }

    public function pollSubmissionUpdates(MeterReadingSubmissionNotifier $notifier): void
    {
        if (! $this->building_id) {
            return;
        }

        $events = $notifier->pullForBuildingPeriod(
            (int) $this->building_id,
            $this->statusYear,
            $this->statusMonth,
        );

        foreach ($events as $event) {
            $this->dispatch(
                'manager-submission-toast',
                message: __('Квартира № :number сдала показания', [
                    'number' => $event['apartment_number'],
                ]),
            );
        }

        $this->skipRender();
    }

    #[Computed]
    public function buildings(): Collection
    {
        return Building::query()->withCount('apartments')->orderBy('name')->get();
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->building_id) {
            return [
                'total' => 0,
                'submitted' => 0,
                'debt' => 0,
                'no_login' => 0,
                'no_resident' => 0,
            ];
        }

        return app(ManagerBuildingStats::class)->forBuilding(
            (int) $this->building_id,
            $this->statusYear,
            $this->statusMonth,
        );
    }

    #[Computed]
    public function periodLabel(): string
    {
        return Carbon::create($this->statusYear, $this->statusMonth, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    #[Computed]
    public function submissionProgress(): int
    {
        $total = $this->stats['total'];

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->stats['submitted'] / $total) * 100);
    }

    public function render(): View
    {
        return view('livewire.manager.manager-dashboard');
    }
}
