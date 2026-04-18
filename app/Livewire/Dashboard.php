<?php

namespace App\Livewire;

use App\Models\Apartment;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\MeterPhotoOcrService;
use App\Services\MeterSubmissionWindow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $cold_m3 = '';

    public string $hot_m3 = '';

    /** @var TemporaryUploadedFile|null */
    public $meterPhoto = null;

    public string $historySearch = '';

    public string $historySortField = 'period';

    public bool $historySortAsc = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            return;
        }

        $window = app(MeterSubmissionWindow::class);
        $period = $window->residentActionablePeriodAt();
        if (! $period) {
            return;
        }

        $reading = MeterReading::query()
            ->where('apartment_id', $user->apartment_id)
            ->where('year', $period['year'])
            ->where('month', $period['month'])
            ->first();

        if ($reading) {
            $this->cold_m3 = (string) $reading->cold_m3;
            $this->hot_m3 = (string) $reading->hot_m3;
        }
    }

    public function saveReading(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            abort(403);
        }

        $apartment = $user->apartment()->with('building')->first();
        if (! $apartment) {
            session()->flash('reading_error', 'Квартира не назначена. Обратитесь к управляющему.');

            return;
        }

        $window = app(MeterSubmissionWindow::class);
        $period = $window->residentActionablePeriodAt();
        if (! $period) {
            session()->flash('reading_error', 'Сейчас не период приёма показаний (с 25-го по 10-е число).');

            return;
        }

        $existing = MeterReading::query()
            ->where('apartment_id', $apartment->id)
            ->where('year', $period['year'])
            ->where('month', $period['month'])
            ->first();

        if ($existing) {
            Gate::authorize('update-meter-reading', $existing);
        } else {
            Gate::authorize('record-meter-reading', [$apartment, $period['year'], $period['month']]);
        }

        $this->validate([
            'cold_m3' => ['required', 'numeric', 'min:0'],
            'hot_m3' => ['required', 'numeric', 'min:0'],
        ], [], [
            'cold_m3' => 'холодная вода',
            'hot_m3' => 'горячая вода',
        ]);

        MeterReading::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'year' => $period['year'],
                'month' => $period['month'],
            ],
            [
                'cold_m3' => $this->cold_m3,
                'hot_m3' => $this->hot_m3,
                'recorded_by_user_id' => $user->id,
                'entered_by_manager' => false,
            ],
        );

        session()->flash('reading_status', 'Показания сохранены.');
    }

    public function recognizeMeterFromPhoto(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            abort(403);
        }

        $apartment = $user->apartment()->with('building')->first();
        if (! $apartment) {
            session()->flash('reading_error', 'Квартира не назначена. Обратитесь к управляющему.');

            return;
        }

        $window = app(MeterSubmissionWindow::class);
        $period = $window->residentActionablePeriodAt();
        if (! $period) {
            session()->flash('reading_error', 'Сейчас не период приёма показаний (с 25-го по 10-е число).');

            return;
        }

        $existing = MeterReading::query()
            ->where('apartment_id', $apartment->id)
            ->where('year', $period['year'])
            ->where('month', $period['month'])
            ->first();

        if ($existing) {
            Gate::authorize('update-meter-reading', $existing);
        } else {
            Gate::authorize('record-meter-reading', [$apartment, $period['year'], $period['month']]);
        }

        $this->validate([
            'meterPhoto' => ['required', 'image', 'max:10240'],
        ], [], [
            'meterPhoto' => 'фото счётчика',
        ]);

        if (! $this->meterPhoto instanceof TemporaryUploadedFile) {
            session()->flash('reading_error', 'Не удалось загрузить файл.');

            return;
        }

        $result = app(MeterPhotoOcrService::class)->suggestFromImageBytes($this->meterPhoto->get());

        if ($result['cold'] !== null) {
            $this->cold_m3 = $result['cold'];
        }
        if ($result['hot'] !== null) {
            $this->hot_m3 = $result['hot'];
        }

        session()->flash('reading_ocr_hint', $result['hint']);
        $this->reset('meterPhoto');
    }

    public function updatedHistorySearch(): void
    {
        $this->resetPage('historyPage');
    }

    public function sortHistoryBy(string $field): void
    {
        if ($this->historySortField === $field) {
            $this->historySortAsc = ! $this->historySortAsc;
        } else {
            $this->historySortField = $field;
            $this->historySortAsc = true;
        }

        $this->resetPage('historyPage');
    }

    #[Computed]
    public function residentApartment(): ?Apartment
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            return null;
        }

        return $user->apartment()->with('building')->first();
    }

    #[Computed]
    public function residentPeriod(): ?array
    {
        if ($this->residentApartment === null) {
            return null;
        }

        return app(MeterSubmissionWindow::class)->residentActionablePeriodAt();
    }

    #[Computed]
    public function residentPeriodCloseFormatted(): ?string
    {
        $p = $this->residentPeriod;
        if ($p === null) {
            return null;
        }

        return app(MeterSubmissionWindow::class)
            ->closesAt($p['year'], $p['month'])
            ->translatedFormat('d.m.Y');
    }

    #[Computed]
    public function residentCanEditMeter(): bool
    {
        $p = $this->residentPeriod;
        if ($p === null) {
            return false;
        }

        return app(MeterSubmissionWindow::class)->isOpenForResident($p['year'], $p['month']);
    }

    #[Computed]
    public function residentCurrentReading(): ?MeterReading
    {
        $user = auth()->user();
        $period = $this->residentPeriod;
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id || $period === null) {
            return null;
        }

        return MeterReading::query()
            ->where('apartment_id', $user->apartment_id)
            ->where('year', $period['year'])
            ->where('month', $period['month'])
            ->first();
    }

    #[Computed]
    public function residentSubmittedForCurrentPeriod(): bool
    {
        return $this->residentCanEditMeter && $this->residentCurrentReading !== null;
    }

    #[Computed]
    public function readingHistory(): Collection
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            return collect();
        }

        return MeterReading::query()
            ->where('apartment_id', $user->apartment_id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(24)
            ->get();
    }

    #[Computed]
    public function readingHistoryRows(): LengthAwarePaginator
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isResident() || ! $user->apartment_id) {
            return MeterReading::query()->whereRaw('0 = 1')->paginate(12, ['*'], 'historyPage');
        }

        $query = MeterReading::query()->where('apartment_id', $user->apartment_id);

        if ($this->historySearch !== '') {
            $s = '%'.addcslashes($this->historySearch, '%_\\').'%';
            $query->whereRaw("CONCAT(year, '-', LPAD(month, 2, '0')) like ?", [$s]);
        }

        $dir = $this->historySortAsc ? 'asc' : 'desc';

        match ($this->historySortField) {
            'cold' => $query->orderBy('cold_m3', $dir),
            'hot' => $query->orderBy('hot_m3', $dir),
            default => $query->orderBy('year', $dir)->orderBy('month', $dir),
        };

        return $query->paginate(12, ['*'], 'historyPage');
    }

    #[Computed]
    public function readingHistoryRowsWithConsumption(): Collection
    {
        $rows = $this->readingHistoryRows->getCollection();

        return $rows->map(function (MeterReading $row) {
            [$py, $pm] = $this->previousPeriod($row->year, $row->month);
            $prev = MeterReading::query()
                ->where('apartment_id', $row->apartment_id)
                ->where('year', $py)
                ->where('month', $pm)
                ->first();

            return [
                'row' => $row,
                'cold_consumption' => $prev ? number_format((float) $row->cold_m3 - (float) $prev->cold_m3, 3, '.', '') : null,
                'hot_consumption' => $prev ? number_format((float) $row->hot_m3 - (float) $prev->hot_m3, 3, '.', '') : null,
            ];
        });
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

    /**
     * @return array{from: int, to: int, nextOpens: int}
     */
    #[Computed]
    public function submissionHintGap(): array
    {
        return [
            'from' => (int) config('water.submission_closes_day') + 1,
            'to' => (int) config('water.submission_opens_day') - 1,
            'nextOpens' => (int) config('water.submission_opens_day'),
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
