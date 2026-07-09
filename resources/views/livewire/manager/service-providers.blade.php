<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="k16-page-title">{{ __('Поставщики') }}</h1>
            <p class="mt-2 k16-page-subtitle">{{ __('Каталог услуг и тарифы поставщиков. При расчёте показаний используются тарифы ХВС и ГВС.') }}</p>
        </div>

        @if (session('mgr_ok'))
            <div class="k16-alert-success">{{ session('mgr_ok') }}</div>
        @endif
        @if (session('mgr_err'))
            <div class="k16-alert-danger">{{ session('mgr_err') }}</div>
        @endif

        <div class="space-y-3">
            @forelse ($this->providers as $provider)
                <div wire:key="provider-{{ $provider->id }}" class="k16-card p-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-k16-lead font-bold text-k16-text">{{ $provider->name }}</p>
                        <span class="text-k16-body font-mono text-k16-text-muted">{{ $provider->code }}</span>
                    </div>

                    @if ($provider->rates->isNotEmpty())
                        <ul class="mt-3 space-y-2 text-k16-body">
                            @foreach ($provider->rates->sortBy(fn ($rate) => $rate->service?->sort_order ?? 0) as $rate)
                                <li wire:key="provider-{{ $provider->id }}-rate-{{ $rate->id }}" class="flex items-center justify-between gap-3">
                                    <span class="text-k16-text">{{ $rate->service?->displayName() ?? $rate->service_code }}</span>
                                    <span class="font-semibold tabular-nums">{{ $this->formatPrice($rate->price, $rate->service?->unit) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-k16-body text-k16-text-muted">{{ __('Тарифы не заданы — откройте редактирование и добавьте услуги.') }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="startEdit({{ $provider->id }})" class="k16-btn-primary">
                            {{ __('Изменить') }}
                        </button>
                        <x-k16.confirm-button
                            wire-method="deleteProvider"
                            :wire-param="$provider->id"
                            :title="__('Удалить поставщика?')"
                            :text="__('Поставщик «:name» и все его тарифы будут удалены.', ['name' => $provider->name])"
                            tone="danger"
                            class="k16-btn-secondary"
                        >
                            {{ __('Удалить') }}
                        </x-k16.confirm-button>
                    </div>
                </div>
            @empty
                <div class="k16-card p-6 text-k16-text-muted">{{ __('Пока нет поставщиков — добавьте первого ниже.') }}</div>
            @endforelse
        </div>

        <form wire:submit="createProvider" class="k16-card border-dashed p-5 space-y-4">
            <p class="text-k16-lead font-bold text-k16-text">{{ __('Новый поставщик') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="new-code" :value="__('Код')" />
                    <x-text-input wire:model="new_code" id="new-code" class="mt-1 block w-full font-mono uppercase" placeholder="JURMALAS_UDENS" />
                    <x-input-error :messages="$errors->get('new_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="new-name" :value="__('Название')" />
                    <x-text-input wire:model="new_name" id="new-name" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('new_name')" class="mt-1" />
                </div>
            </div>
            <p class="text-k16-body text-k16-text-muted">{{ __('После создания откройте «Изменить» и добавьте тарифы по услугам из каталога.') }}</p>
            <button type="submit" class="k16-btn-primary">{{ __('Добавить поставщика') }}</button>
        </form>
    </div>

    <x-modal name="edit-provider" variant="k16" :show="$editingId !== null" focusable>
        <form wire:submit="saveProvider" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Редактирование поставщика') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit-code" :value="__('Код')" />
                    <x-text-input wire:model="edit_code" id="edit-code" class="mt-1 block w-full font-mono uppercase" required />
                    <x-input-error :messages="$errors->get('edit_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-name" :value="__('Название')" />
                    <x-text-input wire:model="edit_name" id="edit-name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_name')" class="mt-1" />
                </div>
            </div>

            <div class="rounded-lg border border-k16-border p-4 space-y-3">
                <p class="text-k16-lead font-semibold text-k16-text">{{ __('Тарифы') }}</p>

                @forelse ($this->editingRates as $rate)
                    <div wire:key="edit-rate-{{ $rate->id }}" class="flex items-center justify-between gap-3">
                        <span class="text-k16-body text-k16-text">{{ $rate->service?->displayName() ?? $rate->service_code }}</span>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold tabular-nums">{{ $this->formatPrice($rate->price, $rate->service?->unit) }}</span>
                            <x-k16.confirm-button
                                wire-method="deleteRate"
                                :wire-param="$rate->id"
                                :title="__('Удалить тариф?')"
                                :text="__('Тариф «:name» будет удалён.', ['name' => $rate->service?->displayName() ?? $rate->service_code])"
                                tone="danger"
                                class="k16-btn-secondary !px-3 !py-1 text-sm"
                            >
                                {{ __('Удалить') }}
                            </x-k16.confirm-button>
                        </div>
                    </div>
                @empty
                    <p class="text-k16-body text-k16-text-muted">{{ __('Пока нет тарифов.') }}</p>
                @endforelse

                @if ($this->availableServicesForNewRate->isNotEmpty())
                    <div class="border-t border-k16-border pt-3 space-y-3">
                        <p class="text-k16-body font-medium text-k16-text">{{ __('Добавить тариф') }}</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label for="new-rate-service" :value="__('Услуга')" />
                                <select wire:model.live="new_rate_service" id="new-rate-service" class="mt-1 block w-full rounded-md border-k16-border shadow-sm">
                                    <option value="">{{ __('Выберите услугу') }}</option>
                                    @foreach ($this->availableServicesForNewRate as $service)
                                        <option value="{{ $service->code }}">{{ $service->displayName() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('new_rate_service')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="new-rate-price" :value="__('Цена')" />
                                <x-text-input wire:model.live="new_rate_price" id="new-rate-price" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" placeholder="4.55" wire:keydown.enter.prevent="addRate" />
                                <x-input-error :messages="$errors->get('new_rate_price')" class="mt-1" />
                            </div>
                        </div>
                        <button type="button" wire:click.prevent="addRate" class="k16-btn-secondary">{{ __('Добавить тариф') }}</button>
                        <p class="text-k16-body text-k16-text-muted">{{ __('Или нажмите «Сохранить» — заполненная строка добавится автоматически.') }}</p>
                    </div>
                @else
                    <p class="border-t border-k16-border pt-3 text-k16-body text-k16-text-muted">{{ __('Все услуги из каталога уже добавлены.') }}</p>
                @endif
            </div>

            <p class="text-k16-body text-k16-text-muted">{{ __('Новые тарифы применятся при следующем сохранении показаний. Уже рассчитанные суммы не изменятся.') }}</p>
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEdit" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>
</div>
