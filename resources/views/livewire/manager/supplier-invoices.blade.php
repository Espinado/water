<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="k16-page-title">{{ __('Счета поставщиков') }}</h1>
                <p class="mt-2 k16-page-subtitle">{{ __('Счета от поставщиков коммунальных услуг по всем домам. Поиск и сортировка по периодам.') }}</p>
            </div>
            @if ($this->providers->isNotEmpty())
                <button type="button" wire:click="openInvoiceModal" class="k16-btn-primary w-full shrink-0 sm:w-auto">
                    {{ __('Ввести счёт') }}
                </button>
            @endif
        </div>

        @if (session('mgr_ok'))
            <div class="k16-alert-success">{{ session('mgr_ok') }}</div>
        @endif

        @if ($this->providers->isEmpty())
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Пока нет поставщиков — добавьте поставщика и тарифы в разделе «Поставщики».') }}</div>
        @else
            <div class="k16-card space-y-4 p-5">
                @if ($this->providers->count() > 1)
                    <div>
                        <x-input-label for="page-provider" :value="__('Поставщик')" />
                        <select wire:model.live="service_provider_id" id="page-provider" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                            @foreach ($this->providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <p class="text-k16-body font-semibold text-k16-text">{{ $this->providers->first()->name }}</p>
                @endif

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input-label for="invoice-search" :value="__('Поиск по периоду')" />
                        <x-text-input
                            wire:model.live.debounce.300ms="search"
                            id="invoice-search"
                            type="search"
                            class="mt-1 block w-full"
                            :placeholder="__('2026, 2026-06, июнь…')"
                        />
                    </div>
                    <button type="button" wire:click="toggleSort" class="k16-btn-secondary w-full sm:w-auto">
                        {{ $sortNewestFirst ? __('Сначала новые') : __('Сначала старые') }}
                        {{ $sortNewestFirst ? '↓' : '↑' }}
                    </button>
                </div>
            </div>

            <div class="k16-card overflow-hidden">
                @if ($this->invoices->isEmpty())
                    <p class="p-6 text-k16-body text-k16-text-muted">
                        {{ $search !== '' ? __('Счета за такой период не найдены.') : __('Пока нет счетов — нажмите «Ввести счёт».') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[32rem] text-k16-body">
                            <thead>
                                <tr class="border-b border-k16-border bg-k16-bg/60 text-left text-k16-text-muted">
                                    <th class="px-4 py-3 font-medium">{{ __('Период') }}</th>
                                    @if ($this->selectedProviderIsWater)
                                        <th class="px-4 py-3 font-medium">{{ __('ХВС, м³') }}</th>
                                        <th class="px-4 py-3 font-medium">{{ __('ГВС, м³') }}</th>
                                        <th class="px-4 py-3 font-medium">{{ __('ХВС, €') }}</th>
                                        <th class="px-4 py-3 font-medium">{{ __('ГВС, €') }}</th>
                                    @endif
                                    <th class="px-4 py-3 font-medium">{{ __('Итого, €') }}</th>
                                    <th class="px-4 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="text-k16-text">
                                @foreach ($this->invoices as $invoice)
                                    <tr wire:key="invoice-row-{{ $invoice->id }}" class="border-b border-k16-border/60 last:border-0">
                                        <td class="px-4 py-3 font-semibold whitespace-nowrap">{{ $this->formatPeriodLabel($invoice->year, $invoice->month) }}</td>
                                        @if ($this->selectedProviderIsWater)
                                            <td class="px-4 py-3 tabular-nums">{{ $this->formatVolume($invoice->cold_m3) }}</td>
                                            <td class="px-4 py-3 tabular-nums">{{ $this->formatVolume($invoice->hot_m3) }}</td>
                                            <td class="px-4 py-3 tabular-nums">{{ $this->formatMoney($invoice->cold_amount) }}</td>
                                            <td class="px-4 py-3 tabular-nums">{{ $this->formatMoney($invoice->hot_amount) }}</td>
                                        @endif
                                        <td class="px-4 py-3 font-semibold tabular-nums">{{ $this->formatMoney($this->invoiceTotal($invoice)) }}</td>
                                        <td class="px-4 py-3 text-end">
                                            <button type="button" wire:click="editInvoice({{ $invoice->id }})" class="k16-btn-secondary !px-3 !py-1.5 text-sm">
                                                {{ __('Изменить') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <x-modal name="edit-invoice" variant="k16" :show="$invoiceModalOpen" focusable>
        <form wire:submit="saveInvoice" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Счёт поставщика') }}</h2>
            <p class="text-k16-body text-k16-text-muted">
                @if ($this->selectedProviderIsWater)
                    {{ __('Укажите период и объёмы/суммы по ХВС и ГВС из счёта поставщика воды.') }}
                @else
                    {{ __('Укажите период и общую сумму счёта от поставщика.') }}
                @endif
            </p>

            <div>
                <x-input-label for="modal-provider" :value="__('Поставщик')" />
                <select wire:model.live="service_provider_id" id="modal-provider" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                    @foreach ($this->providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('service_provider_id')" class="mt-1" />
            </div>

            <div class="rounded-xl border border-k16-border p-4 space-y-3">
                <p class="font-semibold text-k16-text">{{ __('Период счёта') }}</p>
                <div class="flex flex-wrap gap-3">
                    <div class="w-28">
                        <x-input-label for="form-year" :value="__('Год')" />
                        <x-text-input wire:model.live="form_year" id="form-year" type="number" class="mt-1 block w-full" min="2000" max="2100" />
                        <x-input-error :messages="$errors->get('form_year')" class="mt-1" />
                    </div>
                    <div class="min-w-[10rem] flex-1 sm:max-w-xs">
                        <x-input-label for="form-month" :value="__('Месяц')" />
                        <select wire:model.live="form_month" id="form-month" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                            @for ($mo = 1; $mo <= 12; $mo++)
                                <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('form_month')" class="mt-1" />
                    </div>
                </div>
                <p class="text-k16-body text-k16-text-muted">{{ __('Счёт за: :period', ['period' => $this->formPeriodLabel]) }}</p>
            </div>

            @if ($this->selectedProviderIsWater)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-4 rounded-xl border border-k16-border p-4">
                        <p class="font-semibold text-k16-text">{{ __('Холодная вода') }}</p>
                        <div>
                            <x-input-label for="modal-cold-m3" :value="__('Объём, м³')" />
                            <x-text-input wire:model="cold_m3" id="modal-cold-m3" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('cold_m3')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="modal-cold-amount" :value="__('Сумма, €')" />
                            <x-text-input wire:model="cold_amount" id="modal-cold-amount" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('cold_amount')" class="mt-1" />
                        </div>
                    </div>

                    <div class="space-y-4 rounded-xl border border-k16-border p-4">
                        <p class="font-semibold text-k16-text">{{ __('Горячая вода') }}</p>
                        <div>
                            <x-input-label for="modal-hot-m3" :value="__('Объём, м³')" />
                            <x-text-input wire:model="hot_m3" id="modal-hot-m3" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('hot_m3')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="modal-hot-amount" :value="__('Сумма, €')" />
                            <x-text-input wire:model="hot_amount" id="modal-hot-amount" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('hot_amount')" class="mt-1" />
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-k16-border p-4">
                    <x-input-label for="modal-total-amount" :value="__('Сумма счёта, €')" />
                    <x-text-input wire:model="total_amount" id="modal-total-amount" type="text" inputmode="decimal" class="mt-1 block w-full text-right text-lg font-semibold" />
                    <x-input-error :messages="$errors->get('total_amount')" class="mt-1" />
                </div>
            @endif

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelInvoiceModal" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить счёт') }}</button>
            </div>
        </form>
    </x-modal>
</div>
