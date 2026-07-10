<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="k16-page-title">{{ __('Счета поставщиков') }}</h1>
            <p class="mt-2 k16-page-subtitle">{{ __('Счёт за воду по всем домам и отчёт о потерях относительно показаний счётчиков.') }}</p>
        </div>

        @if (session('mgr_ok'))
            <div class="k16-alert-success">{{ session('mgr_ok') }}</div>
        @endif

        <x-manager.context-bar
            :buildings="collect()"
            year-model="year"
            month-model="month"
            :period-title="__('Период отчёта')"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->waterProviders->isEmpty())
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Нет поставщиков воды — добавьте поставщика и тарифы ХВС/ГВС в разделе «Поставщики».') }}</div>
        @else
            <div class="k16-card space-y-4 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-k16-lead font-bold text-k16-text">{{ __('Счёт поставщика') }}</p>
                        <p class="mt-1 text-k16-body text-k16-text-muted">{{ __('Период: :period', ['period' => $this->periodLabel]) }}</p>
                    </div>
                    <button type="button" wire:click="openInvoiceModal" class="k16-btn-primary w-full sm:w-auto">
                        {{ $this->currentInvoice ? __('Изменить счёт') : __('Ввести счёт') }}
                    </button>
                </div>

                @if ($this->waterProviders->count() > 1)
                    <div>
                        <x-input-label for="page-provider" :value="__('Поставщик')" />
                        <select wire:model.live="service_provider_id" id="page-provider" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                            @foreach ($this->waterProviders as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($this->waterProviders->count() === 1)
                    <p class="text-k16-body text-k16-text">{{ $this->waterProviders->first()->name }}</p>
                @endif

                @if ($this->currentInvoice)
                    <dl class="grid gap-3 sm:grid-cols-2 text-k16-body">
                        <div class="rounded-xl border border-k16-border p-4">
                            <dt class="font-semibold text-k16-text">{{ __('Холодная вода') }}</dt>
                            <dd class="mt-2 tabular-nums">{{ $this->formatVolume($this->currentInvoice->cold_m3) }}</dd>
                            <dd class="mt-1 font-semibold tabular-nums">{{ $this->formatMoney($this->currentInvoice->cold_amount) }}</dd>
                        </div>
                        <div class="rounded-xl border border-k16-border p-4">
                            <dt class="font-semibold text-k16-text">{{ __('Горячая вода') }}</dt>
                            <dd class="mt-2 tabular-nums">{{ $this->formatVolume($this->currentInvoice->hot_m3) }}</dd>
                            <dd class="mt-1 font-semibold tabular-nums">{{ $this->formatMoney($this->currentInvoice->hot_amount) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-k16-body text-k16-text-muted">{{ __('Счёт за этот период ещё не введён.') }}</p>
                @endif
            </div>

            <div class="k16-card space-y-5 p-5">
                <div>
                    <p class="text-k16-lead font-bold text-k16-text">{{ __('Потери воды') }}</p>
                    <p class="mt-1 text-k16-body text-k16-text-muted">{{ __('Период: :period. Сравнение по всем домам.', ['period' => $this->periodLabel]) }}</p>
                </div>

                @php
                    $report = $this->lossReport;
                    $consumption = $report['consumption'];
                @endphp

                @if ($consumption['missing_apartments'] > 0 || $consumption['incomplete_apartments'] > 0)
                    <div class="k16-alert-warning">
                        @if ($consumption['missing_apartments'] > 0)
                            <p>{{ __('Без показаний: :count из :total квартир.', ['count' => $consumption['missing_apartments'], 'total' => $consumption['total_apartments']]) }}</p>
                        @endif
                        @if ($consumption['incomplete_apartments'] > 0)
                            <p>{{ __('Показания есть, но расход не рассчитан: :count кв.', ['count' => $consumption['incomplete_apartments']]) }}</p>
                        @endif
                        <p class="mt-1">{{ __('Потери могут быть завышены, пока не сданы все показания.') }}</p>
                    </div>
                @endif

                @if (! $report['has_invoice'])
                    <p class="text-k16-body text-k16-text-muted">{{ __('Введите счёт поставщика, чтобы увидеть потери.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[32rem] text-k16-body">
                            <thead>
                                <tr class="border-b border-k16-border text-left text-k16-text-muted">
                                    <th class="pb-2 pe-3 font-medium"></th>
                                    <th class="pb-2 pe-3 font-medium">{{ __('ХВС, м³') }}</th>
                                    <th class="pb-2 pe-3 font-medium">{{ __('ГВС, м³') }}</th>
                                    <th class="pb-2 pe-3 font-medium">{{ __('ХВС, €') }}</th>
                                    <th class="pb-2 font-medium">{{ __('ГВС, €') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-k16-text">
                                <tr class="border-b border-k16-border/60">
                                    <td class="py-3 pe-3 font-medium">{{ __('Счёт поставщика') }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatVolume($report['invoice']['cold_m3']) }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatVolume($report['invoice']['hot_m3']) }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatMoney($report['invoice']['cold_amount']) }}</td>
                                    <td class="py-3 tabular-nums">{{ $this->formatMoney($report['invoice']['hot_amount']) }}</td>
                                </tr>
                                <tr class="border-b border-k16-border/60">
                                    <td class="py-3 pe-3 font-medium">{{ __('По показаниям') }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatVolume($consumption['cold_m3']) }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatVolume($consumption['hot_m3']) }}</td>
                                    <td class="py-3 pe-3 tabular-nums">{{ $this->formatMoney($consumption['cold_amount']) }}</td>
                                    <td class="py-3 tabular-nums">{{ $this->formatMoney($consumption['hot_amount']) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pe-3 font-bold">{{ __('Потери') }}</td>
                                    <td class="py-3 pe-3 font-bold tabular-nums">{{ $this->formatLoss($report['loss']['cold_m3'], 'm3') }}</td>
                                    <td class="py-3 pe-3 font-bold tabular-nums">{{ $this->formatLoss($report['loss']['hot_m3'], 'm3') }}</td>
                                    <td class="py-3 pe-3 font-bold tabular-nums">{{ $this->formatLoss($report['loss']['cold_amount'], 'eur') }}</td>
                                    <td class="py-3 font-bold tabular-nums">{{ $this->formatLoss($report['loss']['hot_amount'], 'eur') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-k16-body text-k16-text-muted">{{ __('Положительное значение — поставщик выставил больше, чем насчитано по счётчикам.') }}</p>
                @endif
            </div>
        @endif
    </div>

    <x-modal name="edit-invoice" variant="k16" :show="$invoiceModalOpen" focusable>
        <form wire:submit="saveInvoice" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Счёт поставщика') }}</h2>
            <p class="text-k16-body text-k16-text-muted">{{ __('Укажите период, за который поставщик выставил счёт, и суммы из счёта.') }}</p>

            <div>
                <x-input-label for="modal-provider" :value="__('Поставщик')" />
                <select wire:model.live="service_provider_id" id="modal-provider" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                    @foreach ($this->waterProviders as $provider)
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

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelInvoiceModal" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить счёт') }}</button>
            </div>
        </form>
    </x-modal>
</div>
