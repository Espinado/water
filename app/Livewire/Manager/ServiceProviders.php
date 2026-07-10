<?php

namespace App\Livewire\Manager;

use App\Livewire\Concerns\NormalizesDecimalInput;
use App\Models\ProviderServiceRate;
use App\Models\Service;
use App\Models\ServiceProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class ServiceProviders extends Component
{
    use NormalizesDecimalInput;

    /** @var list<string> */
    protected array $decimalInputProperties = ['new_rate_price'];

    public string $new_name = '';

    public ?int $editingId = null;

    public string $edit_name = '';

    public string $new_rate_service = '';

    public string $new_rate_price = '';

    public function createProvider(): void
    {
        $this->validate([
            'new_name' => ['required', 'string', 'max:255'],
        ], [], [
            'new_name' => __('Название'),
        ]);

        ServiceProvider::query()->create([
            'code' => $this->generateProviderCode($this->new_name),
            'name' => $this->new_name,
        ]);

        $this->reset(['new_name']);
        $this->resetValidation();
        unset($this->providers);

        session()->flash('mgr_ok', __('Поставщик добавлен.'));
    }

    public function startEdit(int $providerId): void
    {
        $provider = ServiceProvider::query()->findOrFail($providerId);
        $this->editingId = $provider->id;
        $this->edit_name = $provider->name;
        $this->reset(['new_rate_service', 'new_rate_price']);
        $this->resetValidation();
        unset($this->editingRates, $this->availableServicesForNewRate);
        $this->dispatch('open-modal', 'edit-provider');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->reset(['edit_name', 'new_rate_service', 'new_rate_price']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-provider');
    }

    public function saveProvider(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $provider = ServiceProvider::query()->findOrFail($this->editingId);

        $this->validate([
            'edit_name' => ['required', 'string', 'max:255'],
        ], [], [
            'edit_name' => __('Название'),
        ]);

        if (! $this->persistPendingRate()) {
            return;
        }

        $provider->update([
            'name' => $this->edit_name,
        ]);

        unset($this->providers);
        session()->flash('mgr_ok', __('Поставщик обновлён.'));
        $this->cancelEdit();
    }

    public function addRate(): void
    {
        if ($this->persistPendingRate()) {
            session()->flash('mgr_ok', __('Тариф добавлен.'));
        }
    }

    protected function persistPendingRate(): bool
    {
        if ($this->editingId === null) {
            return true;
        }

        if ($this->new_rate_service === '' && $this->new_rate_price === '') {
            return true;
        }

        $this->validate([
            'new_rate_service' => ['required', 'string', 'exists:services,code'],
            'new_rate_price' => ['required', 'numeric', 'min:0'],
        ], [], [
            'new_rate_service' => __('Услуга'),
            'new_rate_price' => __('Цена'),
        ]);

        $exists = ProviderServiceRate::query()
            ->where('service_provider_id', $this->editingId)
            ->where('service_code', $this->new_rate_service)
            ->exists();

        if ($exists) {
            $this->addError('new_rate_service', __('Тариф для этой услуги уже задан.'));

            return false;
        }

        ProviderServiceRate::query()->create([
            'service_provider_id' => $this->editingId,
            'service_code' => $this->new_rate_service,
            'price' => $this->new_rate_price,
        ]);

        $this->reset(['new_rate_service', 'new_rate_price']);
        $this->resetValidation(['new_rate_service', 'new_rate_price']);
        $this->refreshRateLists();

        return true;
    }

    protected function refreshRateLists(): void
    {
        unset($this->providers, $this->editingRates, $this->availableServicesForNewRate);
    }

    public function deleteRate(int $rateId): void
    {
        $rate = ProviderServiceRate::query()->findOrFail($rateId);

        if ($this->editingId !== null && $rate->service_provider_id !== $this->editingId) {
            return;
        }

        $rate->delete();
        $this->refreshRateLists();
        session()->flash('mgr_ok', __('Тариф удалён.'));
    }

    public function deleteProvider(int $providerId): void
    {
        ServiceProvider::query()->findOrFail($providerId)->delete();
        unset($this->providers);

        if ($this->editingId === $providerId) {
            $this->cancelEdit();
        }

        session()->flash('mgr_ok', __('Поставщик удалён.'));
    }

    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::query()
            ->with(['rates.service'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function editingRates(): Collection
    {
        if ($this->editingId === null) {
            return collect();
        }

        return ProviderServiceRate::query()
            ->where('service_provider_id', $this->editingId)
            ->with('service')
            ->orderBy('service_code')
            ->get();
    }

    #[Computed]
    public function availableServicesForNewRate(): Collection
    {
        if ($this->editingId === null) {
            return collect();
        }

        $usedCodes = ProviderServiceRate::query()
            ->where('service_provider_id', $this->editingId)
            ->pluck('service_code');

        return Service::query()
            ->whereNotIn('code', $usedCodes)
            ->orderBy('sort_order')
            ->get();
    }

    public function formatPrice(mixed $value, ?string $unit = null): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $suffix = match ($unit) {
            'm3' => '/м³',
            'm2' => '/м²',
            default => '',
        };

        return number_format((float) $value, 2, '.', '').' €'.$suffix;
    }

    protected function generateProviderCode(string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug($name, '_'));
        if ($base === '') {
            $base = 'SUPPLIER';
        }

        $code = $base;
        $suffix = 1;

        while (ServiceProvider::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('code', $code)
            ->exists()) {
            $code = $base.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public function render(): View
    {
        return view('livewire.manager.service-providers');
    }
}
