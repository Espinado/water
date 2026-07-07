<div class="manager-mobile-pad py-6 sm:py-10">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('manager.dashboard') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← {{ __('Обзор') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ __('Показания') }}</h1>
            </div>
            <a href="{{ route('manager.apartments', ['filter' => 'debt']) }}" wire:navigate class="inline-flex min-h-[44px] items-center justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-rose-200 hover:bg-rose-700">
                {{ __('Кто не сдал') }}
            </a>
        </div>

        @if (session('mgr_reading_ok'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 ring-1 ring-emerald-100">{{ session('mgr_reading_ok') }}</div>
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
            <div class="app-card p-6 text-slate-600">{{ __('Нет домов. Добавьте их в настройках.') }}</div>
        @elseif (! $building_id)
            <div class="app-card p-6 text-slate-600">{{ __('Выберите дом.') }}</div>
        @else
            <p class="text-sm leading-relaxed text-slate-600">
                {!! __('В таблице: показания за предыдущий календарный месяц (:prev), за выбранный месяц (:curr) и расход (разница, м³).', [
                    'prev' => '<span class="font-semibold text-indigo-900">'.$this->previousPeriodLabel.'</span>',
                    'curr' => '<span class="font-semibold text-indigo-900">'.$this->currentPeriodLabel.'</span>',
                ]) !!}
            </p>

            <div class="space-y-4">
                <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Фильтр') }}">
                    @foreach (['debt' => __('Долг'), 'submitted' => __('Сданы'), 'all' => __('Все')] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $key }}')"
                            @class([
                                'manager-filter-chip rounded-full px-4 py-2 text-sm font-semibold transition min-h-[40px]',
                                'bg-rose-600 text-white shadow-md shadow-rose-200' => $statusFilter === 'debt' && $key === 'debt',
                                'bg-emerald-600 text-white shadow-md shadow-emerald-200' => $statusFilter === 'submitted' && $key === 'submitted',
                                'bg-indigo-600 text-white shadow-md shadow-indigo-200' => $statusFilter === 'all' && $key === 'all',
                                'bg-rose-50 text-rose-800 ring-1 ring-rose-200 hover:bg-rose-100' => $statusFilter !== 'debt' && $key === 'debt',
                                'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-100' => $statusFilter !== 'submitted' && $key === 'submitted',
                                'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200 hover:bg-indigo-100' => $statusFilter !== 'all' && $key === 'all',
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

            <div class="space-y-3 sm:hidden">
                @forelse ($this->rows as $row)
                    @php
                        $isDebt = $row->curr_cold_m3 === null && $row->curr_hot_m3 === null;
                        $cardTone = $isDebt
                            ? 'border-l-4 border-l-rose-500 from-rose-50/80 ring-rose-100'
                            : 'border-l-4 border-l-emerald-500 from-emerald-50/60 ring-emerald-100';
                    @endphp
                    <div class="app-card overflow-hidden bg-gradient-to-r to-white p-4 ring-1 {{ $cardTone }} sm:p-5" wire:key="read-card-{{ $row->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-3">
                                    <p class="text-lg font-bold text-indigo-950">{{ __('Кв. :number', ['number' => $row->number]) }}</p>
                                    @if ($isDebt)
                                        <span class="rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">{{ __('Долг') }}</span>
                                    @else
                                        <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">{{ __('Сданы') }}</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-sm text-slate-600">{{ $this->residentName($row) }}</p>
                            </div>
                            <a href="{{ route('manager.readings.apartment', ['apartment' => $row->id]) }}" wire:navigate class="text-xs font-bold text-indigo-700 hover:text-indigo-900">{{ __('История') }}</a>
                        </div>

                        @if ($this->isEditingApartment($row->id))
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label :value="__('ХВС')" class="text-xs" />
                                    <x-text-input type="text" inputmode="decimal" class="mt-1 w-full text-right font-semibold" wire:model.live="edit_cold" />
                                    <x-input-error :messages="$errors->get('edit_cold')" class="mt-1" />
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Пред.') }} {{ $this->formatM3($row->prev_cold_m3) }}</p>
                                </div>
                                <div>
                                    <x-input-label :value="__('ГВС')" class="text-xs" />
                                    <x-text-input type="text" inputmode="decimal" class="mt-1 w-full text-right font-semibold" wire:model.live="edit_hot" />
                                    <x-input-error :messages="$errors->get('edit_hot')" class="mt-1" />
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Пред.') }} {{ $this->formatM3($row->prev_hot_m3) }}</p>
                                </div>
                                <div class="sm:col-span-2 flex gap-2">
                                    <x-primary-button type="button" class="flex-1 justify-center" wire:click="saveEditingApartment">{{ __('Сохранить') }}</x-primary-button>
                                    <x-secondary-button type="button" class="flex-1 justify-center" wire:click="cancelEditApartment">{{ __('Отмена') }}</x-secondary-button>
                                </div>
                            </div>
                        @else
                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <div><dt class="text-slate-500">{{ __('Пред. ХВС') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatM3($row->prev_cold_m3) }}</dd></div>
                                <div><dt class="text-slate-500">{{ __('Пред. ГВС') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatM3($row->prev_hot_m3) }}</dd></div>
                                <div><dt class="text-slate-500">{{ __('Тек. ХВС') }}</dt><dd class="font-bold tabular-nums text-indigo-900">{{ $this->formatM3($row->curr_cold_m3) }}</dd></div>
                                <div><dt class="text-slate-500">{{ __('Тек. ГВС') }}</dt><dd class="font-bold tabular-nums text-indigo-900">{{ $this->formatM3($row->curr_hot_m3) }}</dd></div>
                                <div><dt class="text-slate-500">{{ __('Расход ХВС') }}</dt><dd class="font-semibold tabular-nums text-sky-800">{{ $this->formatConsumption($row->curr_cold_m3, $row->prev_cold_m3) }}</dd></div>
                                <div><dt class="text-slate-500">{{ __('Расход ГВС') }}</dt><dd class="font-semibold tabular-nums text-sky-800">{{ $this->formatConsumption($row->curr_hot_m3, $row->prev_hot_m3) }}</dd></div>
                            </dl>
                            <button type="button" wire:click="startEditApartment({{ $row->id }})" @class([
                                'mt-4 inline-flex min-h-[44px] w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold text-white sm:w-auto',
                                'bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-200' => $isDebt,
                                'bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200' => ! $isDebt,
                            ])>
                                {{ $isDebt ? __('Ввести показания') : __('Редактировать') }}
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="app-card p-8 text-center text-slate-500">{{ __('Нет квартир или ничего не найдено.') }}</div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <div class="app-card overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">{{ __('Кв.') }}</th>
                                <th class="px-4 py-3 font-semibold">{{ __('Жилец') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Пред. ХВС') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Пред. ГВС') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Тек. ХВС') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Тек. ГВС') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Расход') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ __('Действия') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->rows as $row)
                                @php $isDebt = $row->curr_cold_m3 === null && $row->curr_hot_m3 === null; @endphp
                                <tr wire:key="read-desktop-{{ $row->id }}" @class(['bg-rose-50/40' => $isDebt])>
                                    <td class="px-4 py-3 font-bold text-indigo-950">
                                        {{ $row->number }}
                                        @if ($isDebt)
                                            <span class="ms-2 rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ __('Долг') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $this->residentName($row) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $this->formatM3($row->prev_cold_m3) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ $this->formatM3($row->prev_hot_m3) }}</td>
                                    @if ($this->isEditingApartment($row->id))
                                        <td class="px-4 py-3 text-right"><x-text-input type="text" inputmode="decimal" class="w-24 ml-auto text-right" wire:model.live="edit_cold" /></td>
                                        <td class="px-4 py-3 text-right"><x-text-input type="text" inputmode="decimal" class="w-24 ml-auto text-right" wire:model.live="edit_hot" /></td>
                                        <td class="px-4 py-3 text-right tabular-nums text-sky-800">{{ $this->formatConsumption($this->edit_cold, $row->prev_cold_m3) }} / {{ $this->formatConsumption($this->edit_hot, $row->prev_hot_m3) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <x-primary-button type="button" class="text-xs" wire:click="saveEditingApartment">{{ __('Сохранить') }}</x-primary-button>
                                            <x-secondary-button type="button" class="text-xs" wire:click="cancelEditApartment">{{ __('Отмена') }}</x-secondary-button>
                                        </td>
                                    @else
                                        <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ $this->formatM3($row->curr_cold_m3) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ $this->formatM3($row->curr_hot_m3) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-sky-800">{{ $this->formatConsumption($row->curr_cold_m3, $row->prev_cold_m3) }} / {{ $this->formatConsumption($row->curr_hot_m3, $row->prev_hot_m3) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button type="button" wire:click="startEditApartment({{ $row->id }})" class="text-xs font-bold text-indigo-700 hover:text-indigo-900">{{ $isDebt ? __('Ввести') : __('Ред.') }}</button>
                                            <a href="{{ route('manager.readings.apartment', ['apartment' => $row->id]) }}" wire:navigate class="ms-2 text-xs font-semibold text-slate-600 hover:text-slate-900">{{ __('История') }}</a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pt-2">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>
</div>
