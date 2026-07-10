<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasManagerContext;
use App\Livewire\Concerns\NormalizesDecimalInput;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\MeterReading;
use App\Services\ManagerContext;
use App\Services\RecordMeterReading;
use App\Services\WaterConsumptionAggregator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.manager')]
class MeterReadings extends Component
{
    use HasManagerContext;
    use NormalizesDecimalInput;
    use WithPagination;

    /** @var list<string> */
    protected array $decimalInputProperties = ['edit_cold', 'edit_hot'];

    public ?int $building_id = null;

    public int $year = 0;

    public int $month = 0;

    public string $search = '';

    #[Url(as: 'filter', except: 'all', history: true)]
    public string $statusFilter = 'all';

    public string $sortField = 'number';

    public bool $sortAsc = true;

    public ?int $editing_apartment_id = null;

    public string $edit_cold = '';

    public string $edit_hot = '';

    public function mount(ManagerContext $context): void
    {
        $this->loadManagerContext($context);

        if (! in_array($this->statusFilter, ['all', 'debt', 'submitted'], true)) {
            $this->statusFilter = 'all';
        }
    }

    protected function syncPeriodFromContext(ManagerContext $context): void
    {
        $this->year = $context->year();
        $this->month = $context->month();
    }

    protected function managerPeriodYear(): int
    {
        return $this->year;
    }

    protected function managerPeriodMonth(): int
    {
        return $this->month;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->cancelEditApartment();
    }

    public function updatedBuildingId(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        $this->resetPage();
        $this->cancelEditApartment();
        unset($this->buildingReadingStatus, $this->portfolioReadingStatus);
    }

    public function updatedYear(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        $this->resetPage();
        $this->cancelEditApartment();
        unset($this->buildingReadingStatus, $this->portfolioReadingStatus);
    }

    public function updatedMonth(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        $this->resetPage();
        $this->cancelEditApartment();
        unset($this->buildingReadingStatus, $this->portfolioReadingStatus);
    }

    public function updatedPaginators(mixed $page, string $pageName): void
    {
        if ($pageName === 'page') {
            $this->cancelEditApartment();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->cancelEditApartment();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortField = $field;
            $this->sortAsc = true;
        }
        $this->resetPage();
        $this->cancelEditApartment();
    }

    public function startEditApartment(int $apartmentId): void
    {
        if (! $this->building_id) {
            return;
        }

        $apartment = Apartment::query()
            ->where('building_id', $this->building_id)
            ->whereKey($apartmentId)
            ->firstOrFail();

        $r = MeterReading::query()
            ->where('apartment_id', $apartment->id)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->first();

        $this->editing_apartment_id = $apartment->id;
        $this->edit_cold = $r ? (string) $r->cold_m3 : '';
        $this->edit_hot = $r ? (string) $r->hot_m3 : '';
    }

    public function cancelEditApartment(): void
    {
        $this->editing_apartment_id = null;
        $this->edit_cold = '';
        $this->edit_hot = '';
    }

    public function isEditingApartment(int $apartmentId): bool
    {
        return (int) $this->editing_apartment_id === $apartmentId;
    }

    public function saveEditingApartment(): void
    {
        if ($this->editing_apartment_id === null) {
            return;
        }

        $apartmentId = $this->editing_apartment_id;

        $apartment = Apartment::query()->findOrFail($apartmentId);

        if ($apartment->building_id !== $this->building_id) {
            abort(403);
        }

        Gate::authorize('record-meter-reading', [$apartment, $this->year, $this->month]);

        Validator::make(
            [
                'edit_cold' => $this->edit_cold,
                'edit_hot' => $this->edit_hot,
            ],
            [
                'edit_cold' => ['required', 'numeric', 'min:0'],
                'edit_hot' => ['required', 'numeric', 'min:0'],
            ],
            [],
            ['edit_cold' => __('холодная вода'), 'edit_hot' => __('горячая вода')],
        )->validate();

        $user = auth()->user();

        app(RecordMeterReading::class)->upsert(
            [
                'apartment_id' => $apartmentId,
                'year' => $this->year,
                'month' => $this->month,
            ],
            [
                'cold_m3' => $this->edit_cold,
                'hot_m3' => $this->edit_hot,
                'recorded_by_user_id' => $user->id,
                'entered_by_manager' => true,
            ],
        );

        session()->flash('mgr_reading_ok', __('Сохранено для кв. :number', ['number' => $apartment->number]));
        $this->cancelEditApartment();
        unset($this->buildingReadingStatus, $this->portfolioReadingStatus);
    }

    public function formatM3(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 3, '.', '');
    }

    public function formatCost(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 2, '.', '').' €';
    }

    public function formatConsumption(mixed $current, mixed $previous): string
    {
        if ($current === null || $current === '' || $previous === null || $previous === '') {
            return '—';
        }

        return number_format((float) $current - (float) $previous, 3, '.', '');
    }

    /**
     * @return array{year: int, month: int}
     */
    protected function previousYearMonth(): array
    {
        $m = $this->month - 1;
        $y = $this->year;
        if ($m < 1) {
            $m = 12;
            $y--;
        }

        return ['year' => $y, 'month' => $m];
    }

    public function residentName(Apartment $row): string
    {
        $fn = $row->getAttribute('ru_first_name');
        $ln = $row->getAttribute('ru_last_name');
        $name = $row->getAttribute('ru_name');

        if (($fn === null || $fn === '') && ($ln === null || $ln === '') && $name) {
            return (string) $name;
        }

        $full = trim(($ln ?? '').' '.($fn ?? ''));

        return $full !== '' ? $full : '—';
    }

    #[Computed]
    public function previousPeriodLabel(): string
    {
        $p = $this->previousYearMonth();

        return sprintf('%02d.%04d', $p['month'], $p['year']);
    }

    #[Computed]
    public function currentPeriodLabel(): string
    {
        return sprintf('%02d.%04d', $this->month, $this->year);
    }

    #[Computed]
    public function buildings(): Collection
    {
        return Building::query()->withCount('apartments')->orderBy('name')->get();
    }

    #[Computed]
    public function buildingReadingStatus(): ?array
    {
        if ($this->building_id === null) {
            return null;
        }

        return app(WaterConsumptionAggregator::class)->aggregateForPeriod(
            $this->year,
            $this->month,
            $this->building_id,
        );
    }

    #[Computed]
    public function portfolioReadingStatus(): array
    {
        return app(WaterConsumptionAggregator::class)->aggregateForPeriod($this->year, $this->month);
    }

    protected function rowsQuery(): Builder
    {
        $prev = $this->previousYearMonth();
        $py = $prev['year'];
        $pm = $prev['month'];

        $sub = DB::table('users')
            ->select('apartment_id', DB::raw('MIN(id) as resident_id'))
            ->where('role', UserRole::Resident->value)
            ->whereNotNull('apartment_id')
            ->groupBy('apartment_id');

        $query = Apartment::query()
            ->where('apartments.building_id', $this->building_id)
            ->leftJoin('meter_readings as mr_c', function ($j) {
                $j->on('mr_c.apartment_id', '=', 'apartments.id')
                    ->where('mr_c.year', '=', $this->year)
                    ->where('mr_c.month', '=', $this->month);
            })
            ->leftJoin('meter_readings as mr_p', function ($j) use ($py, $pm) {
                $j->on('mr_p.apartment_id', '=', 'apartments.id')
                    ->where('mr_p.year', '=', $py)
                    ->where('mr_p.month', '=', $pm);
            })
            ->leftJoinSub($sub, 'pr', 'pr.apartment_id', '=', 'apartments.id')
            ->leftJoin('users', 'users.id', '=', 'pr.resident_id')
            ->select('apartments.*')
            ->addSelect([
                'mr_c.cold_m3 as curr_cold_m3',
                'mr_c.hot_m3 as curr_hot_m3',
                'mr_c.cold_cost as curr_cold_cost',
                'mr_c.hot_cost as curr_hot_cost',
                'mr_p.cold_m3 as prev_cold_m3',
                'mr_p.hot_m3 as prev_hot_m3',
                'users.first_name as ru_first_name',
                'users.last_name as ru_last_name',
                'users.name as ru_name',
            ]);

        if ($this->search !== '') {
            $s = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($s) {
                $q->where('apartments.number', 'like', $s)
                    ->orWhere('users.first_name', 'like', $s)
                    ->orWhere('users.last_name', 'like', $s)
                    ->orWhere('users.name', 'like', $s)
                    ->orWhere('users.email', 'like', $s)
                    ->orWhere('users.phone', 'like', $s);
            });
        }

        match ($this->statusFilter) {
            'debt' => $query->whereNull('mr_c.id'),
            'submitted' => $query->whereNotNull('mr_c.id'),
            default => null,
        };

        $dir = $this->sortAsc ? 'asc' : 'desc';

        if ($this->sortField === 'number' && $this->statusFilter !== 'submitted') {
            $query->orderByRaw('CASE WHEN mr_c.id IS NULL THEN 0 ELSE 1 END ASC');
        }

        match ($this->sortField) {
            'prev_cold' => $query->orderBy('mr_p.cold_m3', $dir)->orderBy('apartments.number'),
            'prev_hot' => $query->orderBy('mr_p.hot_m3', $dir)->orderBy('apartments.number'),
            'curr_cold' => $query->orderBy('mr_c.cold_m3', $dir)->orderBy('apartments.number'),
            'curr_hot' => $query->orderBy('mr_c.hot_m3', $dir)->orderBy('apartments.number'),
            'cold_use' => $query->orderByRaw(
                '(CASE WHEN mr_p.cold_m3 IS NULL OR mr_c.cold_m3 IS NULL THEN 1 ELSE 0 END) ASC, (mr_c.cold_m3 - mr_p.cold_m3) '.$dir
            )->orderBy('apartments.number'),
            'hot_use' => $query->orderByRaw(
                '(CASE WHEN mr_p.hot_m3 IS NULL OR mr_c.hot_m3 IS NULL THEN 1 ELSE 0 END) ASC, (mr_c.hot_m3 - mr_p.hot_m3) '.$dir
            )->orderBy('apartments.number'),
            default => $query->orderBy('apartments.number', $dir),
        };

        return $query;
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        if (! $this->building_id || $this->year < 2000 || $this->month < 1 || $this->month > 12) {
            return Apartment::query()->whereRaw('0 = 1')->paginate(15);
        }

        return $this->rowsQuery()->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.manager.meter-readings');
    }
}
