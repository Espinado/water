<?php

namespace App\Livewire\Manager;

use App\Livewire\Concerns\HasManagerContext;
use App\Models\ServiceProvider;
use App\Models\SupplierInvoice;
use App\Services\ManagerContext;
use App\Services\WaterLossReport;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class SupplierInvoices extends Component
{
    use HasManagerContext;

    public int $year = 0;

    public int $month = 0;

    public ?int $building_id = null;

    public ?int $service_provider_id = null;

    public bool $invoiceModalOpen = false;

    public int $form_year = 0;

    public int $form_month = 0;

    public string $cold_m3 = '';

    public string $cold_amount = '';

    public string $hot_m3 = '';

    public string $hot_amount = '';

    public function mount(ManagerContext $context): void
    {
        $this->loadManagerContext($context);
        $this->ensureDefaultProvider();
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

    public function updatedYear(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        unset($this->lossReport, $this->periodLabel, $this->currentInvoice);
    }

    public function updatedMonth(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        unset($this->lossReport, $this->periodLabel, $this->currentInvoice);
    }

    public function updatedServiceProviderId(): void
    {
        if ($this->invoiceModalOpen) {
            $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        }

        unset($this->lossReport, $this->currentInvoice);
    }

    public function openInvoiceModal(): void
    {
        $this->form_year = $this->year;
        $this->form_month = $this->month;
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
        $this->invoiceModalOpen = true;
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-invoice');
    }

    public function cancelInvoiceModal(): void
    {
        $this->invoiceModalOpen = false;
        $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount']);
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

    public function updatedInvoiceModalServiceProvider(): void
    {
        $this->loadInvoiceFormForPeriod($this->form_year, $this->form_month);
    }

    public function saveInvoice(ManagerContext $context): void
    {
        $this->validate([
            'service_provider_id' => ['required', 'integer', 'exists:service_providers,id'],
            'form_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'form_month' => ['required', 'integer', 'min:1', 'max:12'],
            'cold_m3' => ['required', 'numeric', 'min:0'],
            'cold_amount' => ['required', 'numeric', 'min:0'],
            'hot_m3' => ['required', 'numeric', 'min:0'],
            'hot_amount' => ['required', 'numeric', 'min:0'],
        ], [], [
            'service_provider_id' => __('Поставщик'),
            'form_year' => __('Год'),
            'form_month' => __('Месяц'),
            'cold_m3' => __('ХВС, м³'),
            'cold_amount' => __('ХВС, €'),
            'hot_m3' => __('ГВС, м³'),
            'hot_amount' => __('ГВС, €'),
        ]);

        SupplierInvoice::query()->updateOrCreate(
            [
                'service_provider_id' => $this->service_provider_id,
                'year' => $this->form_year,
                'month' => $this->form_month,
            ],
            [
                'cold_m3' => $this->cold_m3,
                'cold_amount' => $this->cold_amount,
                'hot_m3' => $this->hot_m3,
                'hot_amount' => $this->hot_amount,
                'recorded_by_user_id' => auth()->id(),
            ],
        );

        $this->year = $this->form_year;
        $this->month = $this->form_month;
        $context->setPeriod($this->year, $this->month);

        unset($this->lossReport, $this->currentInvoice, $this->periodLabel, $this->formPeriodLabel);
        session()->flash('mgr_ok', __('Счёт поставщика сохранён.'));
        $this->cancelInvoiceModal();
    }

    #[Computed]
    public function waterProviders(): Collection
    {
        return ServiceProvider::query()
            ->whereHas('rates', fn ($query) => $query->whereIn('service_code', ['water_cold', 'water_hot']))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function currentInvoice(): ?SupplierInvoice
    {
        if ($this->service_provider_id === null) {
            return null;
        }

        return SupplierInvoice::query()
            ->where('service_provider_id', $this->service_provider_id)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->first();
    }

    #[Computed]
    public function lossReport(): array
    {
        return app(WaterLossReport::class)->forPeriod(
            $this->year,
            $this->month,
            $this->currentInvoice,
        );
    }

    #[Computed]
    public function periodLabel(): string
    {
        return $this->formatPeriodLabel($this->year, $this->month);
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

    public function formatLoss(mixed $value, string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $formatted = $unit === 'm3'
            ? number_format((float) $value, 3, '.', '').' м³'
            : number_format((float) $value, 2, '.', '').' €';

        $numeric = (float) $value;
        if ($numeric > 0) {
            return '+'.$formatted;
        }

        return $formatted;
    }

    protected function formatPeriodLabel(int $year, int $month): string
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return '—';
        }

        return Carbon::create($year, $month, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }

    protected function ensureDefaultProvider(): void
    {
        if ($this->service_provider_id !== null) {
            return;
        }

        $this->service_provider_id = $this->waterProviders->first()?->id;
    }

    protected function loadInvoiceFormForPeriod(int $year, int $month): void
    {
        if ($this->service_provider_id === null) {
            $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount']);

            return;
        }

        $invoice = SupplierInvoice::query()
            ->where('service_provider_id', $this->service_provider_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($invoice === null) {
            $this->reset(['cold_m3', 'cold_amount', 'hot_m3', 'hot_amount']);

            return;
        }

        $this->cold_m3 = (string) $invoice->cold_m3;
        $this->cold_amount = (string) $invoice->cold_amount;
        $this->hot_m3 = (string) $invoice->hot_m3;
        $this->hot_amount = (string) $invoice->hot_amount;
    }

    public function render(): View
    {
        return view('livewire.manager.supplier-invoices');
    }
}
