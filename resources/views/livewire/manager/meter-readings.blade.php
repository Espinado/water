<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="k16-page-title">{{ __('Показания') }}</h1>
            </div>
            <a href="{{ route('manager.apartments', ['filter' => 'debt']) }}" wire:navigate class="k16-btn-secondary">
                {{ __('Кто не сдал') }}
            </a>
        </div>

        @if (session('mgr_reading_ok'))
            <div class="k16-alert-success">{{ session('mgr_reading_ok') }}</div>
        @endif

        <x-manager.context-bar
            :buildings="$this->buildings"
            :building-id="$building_id"
            year-model="year"
            month-model="month"
            :period-title="__('Расчётный период')"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->buildings->isEmpty())
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Нет домов. Добавьте их в разделе «Дома».') }}</div>
        @elseif (! $building_id)
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Выберите дом.') }}</div>
        @else
            <p class="text-k16-body leading-relaxed text-k16-text-muted">
                {!! __('В списке: показания за предыдущий месяц (:prev), за выбранный (:curr) и расход (м³).', [
                    'prev' => '<span class="font-semibold text-k16-text">'.$this->previousPeriodLabel.'</span>',
                    'curr' => '<span class="font-semibold text-k16-text">'.$this->currentPeriodLabel.'</span>',
                ]) !!}
            </p>

            @php
                $buildingStatus = $this->buildingReadingStatus;
                $portfolioStatus = $this->portfolioReadingStatus;
            @endphp
            @if (
                ($buildingStatus !== null && ($buildingStatus['missing_apartments'] > 0 || $buildingStatus['incomplete_apartments'] > 0))
                || $portfolioStatus['missing_apartments'] > 0
                || $portfolioStatus['incomplete_apartments'] > 0
            )
                <div class="k16-alert-warning space-y-1">
                    @if ($buildingStatus !== null && $buildingStatus['missing_apartments'] > 0)
                        <p>{{ __('В этом доме без показаний: :count из :total квартир.', ['count' => $buildingStatus['missing_apartments'], 'total' => $buildingStatus['total_apartments']]) }}</p>
                    @endif
                    @if ($buildingStatus !== null && $buildingStatus['incomplete_apartments'] > 0)
                        <p>{{ __('В этом доме расход не рассчитан: :count кв.', ['count' => $buildingStatus['incomplete_apartments']]) }}</p>
                    @endif
                    @if ($portfolioStatus['missing_apartments'] > 0)
                        <p>{{ __('По всем домам без показаний: :count из :total квартир.', ['count' => $portfolioStatus['missing_apartments'], 'total' => $portfolioStatus['total_apartments']]) }}</p>
                    @endif
                    @if ($portfolioStatus['incomplete_apartments'] > 0)
                        <p>{{ __('По всем домам расход не рассчитан: :count кв.', ['count' => $portfolioStatus['incomplete_apartments']]) }}</p>
                    @endif
                    @if ($portfolioStatus['missing_apartments'] > 0 || $portfolioStatus['incomplete_apartments'] > 0)
                        <p>{{ __('Потери воды в разделе «Счета» могут быть завышены, пока не сданы все показания.') }}</p>
                    @endif
                </div>
            @endif

            <div class="space-y-4">
                <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Фильтр') }}">
                    @foreach (['all' => __('Все'), 'debt' => __('Долг'), 'submitted' => __('Сданы')] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $key }}')"
                            @class([
                                'k16-filter-chip',
                                'bg-k16-accent text-white' => $statusFilter === 'all' && $key === 'all',
                                'bg-k16-danger text-white' => $statusFilter === 'debt' && $key === 'debt',
                                'bg-k16-success text-white' => $statusFilter === 'submitted' && $key === 'submitted',
                                'border border-k16-border bg-k16-surface text-k16-text' => $statusFilter !== 'all' && $key === 'all',
                                'border border-k16-danger/30 bg-k16-danger-soft text-k16-danger' => $statusFilter !== 'debt' && $key === 'debt',
                                'border border-k16-success/30 bg-k16-success-soft text-k16-success' => $statusFilter !== 'submitted' && $key === 'submitted',
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="max-w-md">
                    <x-input-label for="search" :value="__('Поиск')" />
                    <x-text-input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full rounded-xl" :placeholder="__('Квартира, ФИО, email, телефон…')" />
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->rows as $row)
                    @php
                        $isDebt = $row->curr_cold_m3 === null && $row->curr_hot_m3 === null;
                    @endphp
                    <div class="k16-card p-5" wire:key="read-card-{{ $row->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-k16-lead font-bold text-k16-text">{{ __('Кв. :number', ['number' => $row->number]) }}</p>
                                    @if ($isDebt)
                                        <span class="k16-badge-danger">{{ __('Не сдано') }}</span>
                                    @else
                                        <span class="k16-badge-success">{{ __('Сдано') }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-k16-body text-k16-text-muted">{{ $this->residentName($row) }}</p>
                            </div>
                        </div>

                        @if ($this->isEditingApartment($row->id))
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label :value="__('ХВС')" />
                                    <x-text-input type="text" inputmode="decimal" class="mt-1 w-full text-right font-semibold" wire:model.live="edit_cold" />
                                    <x-input-error :messages="$errors->get('edit_cold')" class="mt-1" />
                                    <p class="mt-1 text-base text-k16-text-muted">{{ __('Пред.') }} {{ $this->formatM3($row->prev_cold_m3) }}</p>
                                </div>
                                <div>
                                    <x-input-label :value="__('ГВС')" />
                                    <x-text-input type="text" inputmode="decimal" class="mt-1 w-full text-right font-semibold" wire:model.live="edit_hot" />
                                    <x-input-error :messages="$errors->get('edit_hot')" class="mt-1" />
                                    <p class="mt-1 text-base text-k16-text-muted">{{ __('Пред.') }} {{ $this->formatM3($row->prev_hot_m3) }}</p>
                                </div>
                                <div class="flex flex-col gap-2 sm:col-span-2">
                                    <button type="button" class="k16-btn-primary w-full" wire:click="saveEditingApartment">{{ __('Сохранить') }}</button>
                                    <button type="button" class="k16-btn-secondary w-full" wire:click="cancelEditApartment">{{ __('Отмена') }}</button>
                                </div>
                            </div>
                        @else
                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-k16-body">
                                <div><dt class="text-k16-text-muted">{{ __('Пред. ХВС') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatM3($row->prev_cold_m3) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Пред. ГВС') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatM3($row->prev_hot_m3) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Тек. ХВС') }}</dt><dd class="font-bold tabular-nums">{{ $this->formatM3($row->curr_cold_m3) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Тек. ГВС') }}</dt><dd class="font-bold tabular-nums">{{ $this->formatM3($row->curr_hot_m3) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Расход ХВС') }}</dt><dd class="font-semibold tabular-nums text-k16-accent">{{ $this->formatConsumption($row->curr_cold_m3, $row->prev_cold_m3) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Расход ГВС') }}</dt><dd class="font-semibold tabular-nums text-k16-accent">{{ $this->formatConsumption($row->curr_hot_m3, $row->prev_hot_m3) }}</dd></div>
                                @if (! $isDebt)
                                    <div><dt class="text-k16-text-muted">{{ __('ХВС, €') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatCost($row->curr_cold_cost) }}</dd></div>
                                    <div><dt class="text-k16-text-muted">{{ __('ГВС, €') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatCost($row->curr_hot_cost) }}</dd></div>
                                @endif
                            </dl>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="startEditApartment({{ $row->id }})" class="k16-btn-primary">
                                    {{ $isDebt ? __('Ввести показания') : __('Редактировать') }}
                                </button>
                                <x-k16.action-menu>
                                    <x-k16.menu-item :href="route('manager.readings.apartment', ['apartment' => $row->id])">
                                        {{ __('История показаний') }}
                                    </x-k16.menu-item>
                                </x-k16.action-menu>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="k16-card p-8 text-center text-k16-text-muted">{{ __('Нет квартир или ничего не найдено.') }}</div>
                @endforelse
            </div>

            <div class="pt-2">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>
</div>
