<?php

namespace App\Livewire\Manager;

use App\Models\Apartment;
use App\Models\MeterReading;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ApartmentReadingsHistory extends Component
{
    use WithPagination;

    public Apartment $apartment;

    public string $search = '';

    public string $sortField = 'period';

    public bool $sortAsc = false;

    public function mount(Apartment $apartment): void
    {
        $this->apartment = $apartment->load('building');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        $query = MeterReading::query()->where('apartment_id', $this->apartment->id);

        if ($this->search !== '') {
            $s = '%'.addcslashes($this->search, '%_\\').'%';
            $query->whereRaw("CONCAT(year, '-', LPAD(month, 2, '0')) like ?", [$s]);
        }

        $dir = $this->sortAsc ? 'asc' : 'desc';

        match ($this->sortField) {
            'cold' => $query->orderBy('cold_m3', $dir),
            'hot' => $query->orderBy('hot_m3', $dir),
            default => $query->orderBy('year', $dir)->orderBy('month', $dir),
        };

        return $query->paginate(20);
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

    public function render(): View
    {
        return view('livewire.manager.apartment-readings-history');
    }
}
