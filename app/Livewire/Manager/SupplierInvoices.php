<?php

namespace App\Livewire\Manager;

use App\Livewire\Concerns\NormalizesDecimalInput;
use App\Models\ServiceProvider;
use App\Models\SupplierInvoice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class SupplierInvoices extends Component
{
    use NormalizesDecimalInput;

    /** @var list<string> */
    protected array $decimalInputProperties = [
        'cold_m3',
        'cold_amount',
        'hot_m3',
        'hot_amount',
        'total_amount',
    ];

    public ?int $service_provider_id = null;

    public string $search = '';

    public bool $sortNewestFirst = true;

    public bool $invoiceModalOpen = false;

    public bool $invoiceDetailOpen = false;

    public ?int $viewingInvoiceId = null;

    public int $form_year = 0;

    public int $form_month = 0;

    public string $cold_m3 = '';

    public string $cold_amount = '';

    public string $hot_m3 = '';

    public string $hot_amount = '';

    public string $total_amount = '';

    public function mount(): void
    {
        $this->ensureDefaultProvider();
    }

    public function updatedServiceProviderId(): void
    {
        if ($this->invoiceModalOpen) {
            $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        }

        unset($this->invoices, $this->selectedProviderIsWater, $this->showAllProviders, $this->viewingInvoice);
    }

    public function setProviderFilter(string $value): void
    {
        $this->service_provider_id = $value === 'all' ? null : (int) $value;

        if ($this->invoiceModalOpen) {
            $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        }

        unset($this->invoices, $this->selectedProviderIsWater, $this->showAllProviders, $this->viewingInvoice);
    }

    public function updatedSearch(): void
    {
        unset($this->invoices);
    }

    public function toggleSort(): void
    {
        $this->sortNewestFirst = ! $this->sortNewestFirst;
        unset($this->invoices);
    }

    public function openInvoiceModal(): void
    {
        if ($this->service_provider_id === null) {
            $this->service_provider_id = $this->providers->first()?->id;
        }

        $now = now();
        $this->form_year = (int) $now->year;
        $this->form_month = (int) $now->month;
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        $this->invoiceModalOpen = true;
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-invoice');
    }

    public function editInvoice(int $invoiceId): void
    {
        $invoice = SupplierInvoice::query()->findOrFail($invoiceId);

        if ($this->service_provider_id !== null && $invoice->service_provider_id !== $this->service_provider_id) {
            return;
        }

        $this->closeInvoiceDetail();
        $this->service_provider_id = $invoice->service_provider_id;
        $this->form_year = $invoice->year;
        $this->form_month = $invoice->month;
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        $this->invoiceModalOpen = true;
        $this->resetValidation();
        unset($this->selectedProviderIsWater);
        $this->dispatch('open-modal', 'edit-invoice');
    }

    public function viewInvoice(int $invoiceId): void
    {
        $invoice = SupplierInvoice::query()->findOrFail($invoiceId);

        if ($this->service_provider_id !== null && $invoice->service_provider_id !== $this->service_provider_id) {
            return;
        }

        $this->viewingInvoiceId = $invoiceId;
        $this->invoiceDetailOpen = true;
        $this->dispatch('open-modal', 'view-invoice');
    }

    public function closeInvoiceDetail(): void
    {
        $this->invoiceDetailOpen = false;
        $this->viewingInvoiceId = null;
        $this->dispatch('close-modal', 'view-invoice');
    }

    public function editFromDetail(): void
    {
        if ($this->viewingInvoiceId === null) {
            return;
        }

        $this->editInvoice($this->viewingInvoiceId);
    }

    public function cancelInvoiceModal(): void
    {
        $this->invoiceModalOpen = false;
        $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount', 'total_amount']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-invoice');
    }

    public function updatedFormYear(): void
    {
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
    }

    public function updatedFormMonth(): void
    {
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
    }

    public function saveInvoice(): void
    {
        $rules = [
            'service_provider_id' => ['required', 'integer', 'exists:service_providers,id'],
            'form_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'form_month' => ['required', 'integer', 'min:1', 'max:12'],
        ];

        if ($this->selectedProviderIsWater) {
            $rules += [
                'cold_m3' => ['required', 'numeric', 'min:0'],
                'cold_amount' => ['required', 'numeric', 'min:0'],
                'hot_m3' => ['required', 'numeric', 'min:0'],
                'hot_amount' => ['required', 'numeric', 'min:0'],
            ];
        } else {
            $rules['total_amount'] = ['required', 'numeric', 'min:0'];
        }

        $this->validate($rules, [], [
            'service_provider_id' => __('Поставщик'),
            'form_year' => __('Год'),
            'form_month' => __('Месяц'),
            'cold_m3' => __('ХВС, м³'),
            'cold_amount' => __('ХВС, €'),
            'hot_m3' => __('ГВС, м³'),
            'hot_amount' => __('ГВС, €'),
            'total_amount' => __('Сумма счёта'),
        ]);

        $payload = [
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->selectedProviderIsWater) {
            $payload += [
                'cold_m3' => $this->cold_m3,
                'cold_amount' => $this->cold_amount,
                'hot_m3' => $this->hot_m3,
                'hot_amount' => $this->hot_amount,
                'total_amount' => null,
            ];
        } else {
            $payload += [
                'cold_m3' => 0,
                'cold_amount' => 0,
                'hot_m3' => 0,
                'hot_amount' => 0,
                'total_amount' => $this->total_amount,
            ];
        }

        SupplierInvoice::query()->updateOrCreate(
            [
                'service_provider_id' => $this->service_provider_id,
                'year' => $this->form_year,
                'month' => $this->form_month,
            ],
            $payload,
        );

        unset($this->invoices, $this->formPeriodLabel);
        session()->flash('mgr_ok', __('Счёт поставщика сохранён.'));
        $this->cancelInvoiceModal();
    }

    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::query()
            ->with('rates')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedProviderIsWater(): bool
    {
        if ($this->service_provider_id === null) {
            return false;
        }

        $provider = $this->providers->firstWhere('id', $this->service_provider_id);

        return $provider !== null && $this->providerSuppliesWater($provider);
    }

    #[Computed]
    public function showAllProviders(): bool
    {
        return $this->service_provider_id === null;
    }

    #[Computed]
    public function viewingInvoice(): ?SupplierInvoice
    {
        if ($this->viewingInvoiceId === null) {
            return null;
        }

        return SupplierInvoice::query()
            ->with(['provider.rates'])
            ->find($this->viewingInvoiceId);
    }

    #[Computed]
    public function invoices(): Collection
    {
        $query = SupplierInvoice::query()->with(['provider.rates']);

        if ($this->service_provider_id !== null) {
            $query->where('service_provider_id', $this->service_provider_id);
        }

        $invoices = $query
            ->get()
            ->filter(fn (SupplierInvoice $invoice) => $this->matchesPeriodSearch($invoice->year, $invoice->month, $this->search));

        return $this->sortNewestFirst
            ? $invoices->sortByDesc(fn (SupplierInvoice $invoice) => $invoice->year * 100 + $invoice->month)->values()
            : $invoices->sortBy(fn (SupplierInvoice $invoice) => $invoice->year * 100 + $invoice->month)->values();
    }

    #[Computed]
    public function formPeriodLabel(): string
    {
        return $this->formatPeriodLabel($this->form_year, $this->form_month);
    }

    public function formatVolume(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 3, '.', '').' м³';
    }

    public function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 2, '.', '').' €';
    }

    public function invoiceTotal(SupplierInvoice $invoice): float
    {
        if ($invoice->total_amount !== null) {
            return (float) $invoice->total_amount;
        }

        return (float) $invoice->cold_amount + (float) $invoice->hot_amount;
    }

    public function invoiceIsWater(SupplierInvoice $invoice): bool
    {
        $provider = $invoice->provider;

        return $provider !== null && $this->providerSuppliesWater($provider);
    }

    public function formatPeriodLabel(int $year, int $month): string
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return '—';
        }

        return Carbon::create($year, $month, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    protected function providerSuppliesWater(ServiceProvider $provider): bool
    {
        if ($provider->relationLoaded('rates')) {
            return $provider->rates->contains(
                fn ($rate) => in_array($rate->service_code, ['water_cold', 'water_hot'], true)
            );
        }

        return $provider->rates()
            ->whereIn('service_code', ['water_cold', 'water_hot'])
            ->exists();
    }

    protected function matchesPeriodSearch(int $year, int $month, string $search): bool
    {
        $term = mb_strtolower(trim($search));

        if ($term === '') {
            return true;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $term, $matches)) {
            return $year === (int) $matches[1] && $month === (int) $matches[2];
        }

        if (preg_match('/^\d{4}$/', $term)) {
            return $year === (int) $term;
        }

        if (preg_match('/^\d{1,2}$/', $term)) {
            return $month === (int) $term;
        }

        $period = Carbon::create($year, $month, 1)->locale(app()->getLocale());
        $needles = [
            mb_strtolower((string) $year),
            mb_strtolower(sprintf('%04d-%02d', $year, $month)),
            mb_strtolower($period->translatedFormat('F Y')),
            mb_strtolower($period->translatedFormat('F')),
            mb_strtolower($period->translatedFormat('M Y')),
        ];

        foreach ($needles as $needle) {
            if (str_contains($needle, $term)) {
                return true;
            }
        }

        return false;
    }

    protected function ensureDefaultProvider(): void
    {
        if ($this->service_provider_id !== null) {
            return;
        }

        if ($this->providers->count() === 1) {
            $this->service_provider_id = $this->providers->first()?->id;
        }
    }

    protected function loadInvoiceFormForPeriod(int $year, int $month): void
    {
        if ($this->service_provider_id === null) {
            $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount', 'total_amount']);

            return;
        }

        $invoice = SupplierInvoice::query()
            ->where('service_provider_id', $this->service_provider_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($invoice === null) {
            $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount', 'total_amount']);

            return;
        }

        $this->cold_m3 = (string) $invoice->cold_m3;
        $this->cold_amount = (string) $invoice->cold_amount;
        $this->hot_m3 = (string) $invoice->hot_m3;
        $this->hot_amount = (string) $invoice->hot_amount;
        $this->total_amount = $invoice->total_amount !== null ? (string) $invoice->total_amount : '';
    }

    public function render(): View
    {
        return view('livewire.manager.supplier-invoices');
    }
}
