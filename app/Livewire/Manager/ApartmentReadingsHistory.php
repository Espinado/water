<?php

namespace App\Livewire\Manager;

use App\Livewire\Concerns\NormalizesDecimalInput;
use App\Models\Apartment;
use App\Models\MeterReading;
use App\Services\MeterSubmissionWindow;
use App\Services\RecordMeterReading;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.manager')]
class ApartmentReadingsHistory extends Component
{
    use NormalizesDecimalInput;
    use WithPagination;

    /** @var list<string> */
    protected array $decimalInputProperties = ['entry_cold', 'entry_hot', 'edit_cold', 'edit_hot'];

    public Apartment $apartment;

    public int $filterYear = 0;

    public int $filterMonth = 0;

    public string $sortField = 'period';

    public bool $sortAsc = false;

    public int $entryYear = 0;

    public int $entryMonth = 0;

    public string $entry_cold = '';

    public string $entry_hot = '';

    public ?int $editingId = null;

    public string $edit_cold = '';

    public string $edit_hot = '';

    public function mount(Apartment $apartment): void
    {
        $this->apartment = $apartment->load([
            'building',
            'users' => fn ($q) => $q->where('role', \App\Enums\UserRole::Resident),
        ]);

        $latest = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $this->filterYear = $latest?->year ?? (int) now()->year;
        $this->filterMonth = $latest?->month ?? (int) now()->month;

        $this->syncEntryToActionablePeriod();
        $this->loadEntryValues();
    }

    protected function syncEntryToActionablePeriod(): void
    {
        $period = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        $this->entryYear = $period['year'] ?? (int) now()->year;
        $this->entryMonth = $period['month'] ?? (int) now()->month;
    }

    #[Computed]
    public function entryLockedPeriodLabel(): ?string
    {
        $period = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        if ($period === null) {
            return null;
        }

        return Carbon::create($period['year'], $period['month'], 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    public function updatedEntryYear(): void
    {
        if ($this->enforceActionableEntryPeriod()) {
            return;
        }

        $this->loadEntryValues();
    }

    public function updatedEntryMonth(): void
    {
        if ($this->enforceActionableEntryPeriod()) {
            return;
        }

        $this->loadEntryValues();
    }

    protected function enforceActionableEntryPeriod(): bool
    {
        $period = app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
        if ($period === null) {
            return false;
        }

        if ($this->entryYear !== $period['year'] || $this->entryMonth !== $period['month']) {
            $this->syncEntryToActionablePeriod();
            $this->loadEntryValues();

            return true;
        }

        return false;
    }

    protected function loadEntryValues(): void
    {
        $reading = $this->entryReading();

        $this->entry_cold = $reading ? (string) $reading->cold_m3 : '';
        $this->entry_hot = $reading ? (string) $reading->hot_m3 : '';
        $this->resetValidation();
    }

    protected function entryReading(): ?MeterReading
    {
        return MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->where('year', $this->entryYear)
            ->where('month', $this->entryMonth)
            ->first();
    }

    #[Computed]
    public function entryAlreadySubmitted(): bool
    {
        return $this->entryReading() !== null;
    }

    public function saveEntry(): void
    {
        if ($this->enforceActionableEntryPeriod()) {
            session()->flash('reading_error', __('Сейчас можно вносить показания только за период приёма: :period.', [
                'period' => $this->entryLockedPeriodLabel,
            ]));

            return;
        }

        if ($this->entryAlreadySubmitted) {
            session()->flash('reading_error', __('Показания за этот период уже внесены. Отредактируйте их в таблице истории ниже.'));

            return;
        }

        Gate::authorize('record-meter-reading', [$this->apartment, $this->entryYear, $this->entryMonth]);

        Validator::make(
            [
                'entry_cold' => $this->entry_cold,
                'entry_hot' => $this->entry_hot,
            ],
            [
                'entry_cold' => ['required', 'numeric', 'min:0'],
                'entry_hot' => ['required', 'numeric', 'min:0'],
            ],
            [],
            ['entry_cold' => __('холодная вода'), 'entry_hot' => __('горячая вода')],
        )->validate();

        app(RecordMeterReading::class)->upsert(
            [
                'apartment_id' => $this->apartment->id,
                'year' => $this->entryYear,
                'month' => $this->entryMonth,
            ],
            [
                'cold_m3' => $this->entry_cold,
                'hot_m3' => $this->entry_hot,
                'recorded_by_user_id' => auth()->id(),
                'entered_by_manager' => true,
            ],
        );

        $this->filterYear = $this->entryYear;
        $this->filterMonth = $this->entryMonth;
        $this->resetPage();
        unset($this->entryAlreadySubmitted);

        session()->flash('reading_saved', __('Показания сохранены за :period.', [
            'period' => \Carbon\Carbon::create($this->entryYear, $this->entryMonth, 1)
                ->locale(app()->getLocale())
                ->translatedFormat('F Y'),
        ]));
    }

    public function startEdit(int $readingId): void
    {
        $reading = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->whereKey($readingId)
            ->firstOrFail();

        $this->editingId = $reading->id;
        $this->edit_cold = (string) $reading->cold_m3;
        $this->edit_hot = (string) $reading->hot_m3;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->edit_cold = '';
        $this->edit_hot = '';
        $this->resetValidation();
    }

    public function isEditing(int $readingId): bool
    {
        return $this->editingId === $readingId;
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $reading = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->whereKey($this->editingId)
            ->firstOrFail();

        Gate::authorize('update-meter-reading', $reading);

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

        app(RecordMeterReading::class)->update($reading, [
            'cold_m3' => $this->edit_cold,
            'hot_m3' => $this->edit_hot,
            'recorded_by_user_id' => auth()->id(),
            'entered_by_manager' => true,
        ]);

        $this->cancelEdit();
        $this->loadEntryValues();
        unset($this->entryAlreadySubmitted);

        session()->flash('reading_saved', __('Показания обновлены за :period.', [
            'period' => \Carbon\Carbon::create($reading->year, $reading->month, 1)
                ->locale(app()->getLocale())
                ->translatedFormat('F Y'),
        ]));
    }

    public function updatedFilterYear(): void
    {
        $this->filterMonth = $this->defaultMonthForYear($this->filterYear);
        $this->resetPage();
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortField = $field;
            $this->sortAsc = $field === 'period' ? false : true;
        }

        $this->resetPage();
    }

    #[Computed]
    public function resident(): ?\App\Models\User
    {
        return $this->apartment->users->first();
    }

    #[Computed]
    public function availableYears(): Collection
    {
        $years = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year);

        if ($years->isEmpty()) {
            return collect([(int) now()->year]);
        }

        if ($this->filterYear > 0 && ! $years->contains($this->filterYear)) {
            $years->push($this->filterYear);
        }

        return $years->unique()->sortDesc()->values();
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        $query = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->where('year', $this->filterYear)
            ->where('month', $this->filterMonth);

        $dir = $this->sortAsc ? 'asc' : 'desc';

        match ($this->sortField) {
            'cold' => $query->orderBy('cold_m3', $dir)->orderByDesc('year')->orderByDesc('month'),
            'hot' => $query->orderBy('hot_m3', $dir)->orderByDesc('year')->orderByDesc('month'),
            default => $query->orderBy('year', $dir)->orderBy('month', $dir),
        };

        return $query->paginate(12);
    }

    public function periodLabel(MeterReading $row): string
    {
        return \Carbon\Carbon::create($row->year, $row->month, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    public function consumptionFor(MeterReading $row, string $field): string
    {
        [$py, $pm] = $this->previousPeriod($row->year, $row->month);

        $prev = MeterReading::query()
            ->where('apartment_id', $row->apartment_id)
            ->where('year', $py)
            ->where('month', $pm)
            ->first();

        if (! $prev) {
            return '—';
        }

        $current = (float) ($field === 'cold' ? $row->cold_m3 : $row->hot_m3);
        $previous = (float) ($field === 'cold' ? $prev->cold_m3 : $prev->hot_m3);

        return number_format($current - $previous, 3, '.', '');
    }

    public function formatCost(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 2, '.', '').' €';
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function previousPeriod(int $year, int $month): array
    {
        $month--;
        if ($month < 1) {
            $month = 12;
            $year--;
        }

        return [$year, $month];
    }

    protected function defaultMonthForYear(int $year): int
    {
        $month = MeterReading::query()
            ->where('apartment_id', $this->apartment->id)
            ->where('year', $year)
            ->orderByDesc('month')
            ->value('month');

        if ($month !== null) {
            return (int) $month;
        }

        if ($year === (int) now()->year) {
            return (int) now()->month;
        }

        return 1;
    }

    public function render(): View
    {
        return view('livewire.manager.apartment-readings-history');
    }
}
