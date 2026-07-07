<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-4 sm:px-0">
                <h1 class="text-3xl sm:text-4xl font-bold text-indigo-900">{{ __('Показания по домам') }}</h1>
                <div class="flex flex-wrap gap-4 text-base">
                    <a href="{{ route('manager.panel') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Дома и доступ') }}</a>
                    <a href="{{ route('manager.apartments') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Квартиры') }}</a>
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Кабинет') }}</a>
                </div>
            </div>

            @if (session('mgr_reading_ok'))
                <div class="rounded-xl bg-emerald-50 p-4 text-base text-emerald-900 mx-4 sm:mx-0">{{ session('mgr_reading_ok') }}</div>
            @endif

            <div class="app-card mx-4 sm:mx-0 overflow-hidden">
                <div class="border-b border-gray-200 px-6 pt-4">
                    <p class="mb-3 text-base font-semibold text-indigo-900">{{ __('Расчётный период (текущие показания и расход)') }}</p>
                    <div class="grid gap-4 pb-4 sm:grid-cols-2 max-w-md">
                        <div>
                            <x-input-label for="y" :value="__('Год')" />
                            <x-text-input wire:model.live="year" id="y" type="number" class="block mt-1 w-full" min="2000" max="2100" />
                        </div>
                        <div>
                            <x-input-label for="m" :value="__('Месяц')" />
                            <select wire:model.live="month" id="m" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @for ($mo = 1; $mo <= 12; $mo++)
                                    <option value="{{ $mo }}">{{ $mo }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    @if ($this->buildings->isNotEmpty())
                        <nav class="-mb-px flex flex-wrap gap-1 sm:gap-2 border-t border-gray-100 pt-3" aria-label="{{ __('Дома') }}">
                            @foreach ($this->buildings as $b)
                                <button
                                    type="button"
                                    wire:click="$set('building_id', {{ $b->id }})"
                                    wire:key="read-tab-{{ $b->id }}"
                                    @class([
                                        'whitespace-nowrap border-b-2 px-3 py-2 text-base font-semibold transition sm:px-4',
                                        'border-indigo-600 text-indigo-600' => (int) $building_id === (int) $b->id,
                                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => (int) $building_id !== (int) $b->id,
                                    ])
                                >
                                    {{ $b->name }}
                                    <span class="font-normal text-gray-400">({{ $b->apartments_count }})</span>
                                </button>
                            @endforeach
                        </nav>
                    @endif
                </div>

                <div class="space-y-4 p-6">
                    <p class="text-base text-slate-700">
                        {!! __('В таблице: показания за предыдущий календарный месяц (:prev), за выбранный месяц (:curr) и расход (разница, м³). Жилец вносит показания в окне с :from-го по :to-е; правка показаний — кнопка «Редактировать» в строке квартиры.', [
                            'prev' => '<span class="font-medium text-gray-800">'.$this->previousPeriodLabel.'</span>',
                            'curr' => '<span class="font-medium text-gray-800">'.$this->currentPeriodLabel.'</span>',
                            'from' => config('water.submission_opens_day'),
                            'to' => config('water.submission_closes_day'),
                        ]) !!}
                    </p>

                    @if ($this->buildings->isEmpty())
                        <p class="text-sm text-gray-500">{{ __('Нет домов. Добавьте их в разделе «Дома и доступ».') }}</p>
                    @elseif (! $building_id)
                        <p class="text-sm text-gray-500">{{ __('Выберите дом во вкладке.') }}</p>
                    @else
                        <div class="max-w-md">
                            <x-input-label for="search" :value="__('Поиск')" />
                            <x-text-input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full" :placeholder="__('Квартира, ФИО, email, телефон…')" />
                        </div>

                        <div class="space-y-3 sm:hidden">
                            @forelse ($this->rows as $row)
                                <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm" wire:key="read-mobile-{{ $row->id }}">
                                    <div class="flex items-center justify-between">
                                        <p class="text-base font-semibold text-indigo-900">{{ __('Кв. :number', ['number' => $row->number]) }}</p>
                                        <a href="{{ route('manager.readings.apartment', ['apartment' => $row->id]) }}" wire:navigate class="text-xs font-semibold text-indigo-700">{{ __('История') }}</a>
                                    </div>
                                    <dl class="mt-3 space-y-1 text-sm">
                                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Пред. ХВС') }}</dt><dd>{{ $this->formatM3($row->prev_cold_m3) }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Пред. ГВС') }}</dt><dd>{{ $this->formatM3($row->prev_hot_m3) }}</dd></div>
                                        @if ($this->isEditingApartment($row->id))
                                            <div class="pt-2 space-y-2">
                                                <x-text-input type="text" inputmode="decimal" class="w-full text-right" wire:model.live="edit_cold" />
                                                <x-input-error :messages="$errors->get('edit_cold')" class="mt-1" />
                                                <x-text-input type="text" inputmode="decimal" class="w-full text-right" wire:model.live="edit_hot" />
                                                <x-input-error :messages="$errors->get('edit_hot')" class="mt-1" />
                                            </div>
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Расход ХВС') }}</dt><dd class="font-medium">{{ $this->formatConsumption($this->edit_cold, $row->prev_cold_m3) }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Расход ГВС') }}</dt><dd class="font-medium">{{ $this->formatConsumption($this->edit_hot, $row->prev_hot_m3) }}</dd></div>
                                            <div class="flex gap-2 pt-2">
                                                <x-primary-button type="button" class="w-full justify-center text-xs" wire:click="saveEditingApartment">{{ __('Сохранить') }}</x-primary-button>
                                                <x-secondary-button type="button" class="w-full justify-center text-xs" wire:click="cancelEditApartment">{{ __('Отмена') }}</x-secondary-button>
                                            </div>
                                        @else
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Тек. ХВС') }}</dt><dd>{{ $this->formatM3($row->curr_cold_m3) }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Тек. ГВС') }}</dt><dd>{{ $this->formatM3($row->curr_hot_m3) }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Расход ХВС') }}</dt><dd class="font-medium">{{ $this->formatConsumption($row->curr_cold_m3, $row->prev_cold_m3) }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Расход ГВС') }}</dt><dd class="font-medium">{{ $this->formatConsumption($row->curr_hot_m3, $row->prev_hot_m3) }}</dd></div>
                                            <x-secondary-button type="button" class="mt-3 w-full justify-center text-xs" wire:click="startEditApartment({{ $row->id }})">{{ __('Редактировать') }}</x-secondary-button>
                                        @endif
                                    </dl>
                                </div>
                            @empty
                                <div class="py-6 text-gray-500">{{ __('Нет квартир или ничего не найдено.') }}</div>
                            @endforelse
                        </div>
                        <div class="hidden overflow-x-auto sm:block">
                            <table class="min-w-full text-base">
                                <thead>
                                    <tr class="border-b text-left text-gray-600">
                                        <th class="pb-2 pr-3">
                                            <button type="button" wire:click="sortBy('number')" class="font-medium hover:text-indigo-600">
                                                {{ __('Кв.') }} @if ($sortField === 'number'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('prev_cold')" class="font-medium hover:text-indigo-600">
                                                {{ __('Пред. ХВС') }} @if ($sortField === 'prev_cold'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('prev_hot')" class="font-medium hover:text-indigo-600">
                                                {{ __('Пред. ГВС') }} @if ($sortField === 'prev_hot'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('curr_cold')" class="font-medium hover:text-indigo-600">
                                                {{ __('Тек. ХВС') }} @if ($sortField === 'curr_cold'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('curr_hot')" class="font-medium hover:text-indigo-600">
                                                {{ __('Тек. ГВС') }} @if ($sortField === 'curr_hot'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('cold_use')" class="font-medium hover:text-indigo-600">
                                                {{ __('Расход ХВС') }} @if ($sortField === 'cold_use'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-3 text-right">
                                            <button type="button" wire:click="sortBy('hot_use')" class="font-medium hover:text-indigo-600">
                                                {{ __('Расход ГВС') }} @if ($sortField === 'hot_use'){{ $sortAsc ? '↑' : '↓' }}@endif
                                            </button>
                                        </th>
                                        <th class="pb-2 pr-0 text-right font-medium text-gray-600 w-36">{{ __('Действия') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->rows as $row)
                                        <tr class="border-b border-gray-100 align-top" wire:key="read-row-{{ $row->id }}">
                                            <td class="py-3 pr-3 font-medium text-gray-900">{{ $row->number }}</td>
                                            <td class="py-3 pr-3 text-right tabular-nums text-gray-700">{{ $this->formatM3($row->prev_cold_m3) }}</td>
                                            <td class="py-3 pr-3 text-right tabular-nums text-gray-700">{{ $this->formatM3($row->prev_hot_m3) }}</td>
                                            @if ($this->isEditingApartment($row->id))
                                                <td class="py-3 pr-3 text-right">
                                                    <x-text-input type="text" inputmode="decimal" class="w-24 sm:w-28 ml-auto text-right" wire:model.live="edit_cold" />
                                                    <x-input-error :messages="$errors->get('edit_cold')" class="mt-1 text-right justify-end" />
                                                </td>
                                                <td class="py-3 pr-3 text-right">
                                                    <x-text-input type="text" inputmode="decimal" class="w-24 sm:w-28 ml-auto text-right" wire:model.live="edit_hot" />
                                                    <x-input-error :messages="$errors->get('edit_hot')" class="mt-1 text-right justify-end" />
                                                </td>
                                                <td class="py-3 pr-3 text-right tabular-nums text-indigo-900">{{ $this->formatConsumption($this->edit_cold, $row->prev_cold_m3) }}</td>
                                                <td class="py-3 pr-3 text-right tabular-nums text-indigo-900">{{ $this->formatConsumption($this->edit_hot, $row->prev_hot_m3) }}</td>
                                            @else
                                                <td class="py-3 pr-3 text-right tabular-nums text-gray-700">{{ $this->formatM3($row->curr_cold_m3) }}</td>
                                                <td class="py-3 pr-3 text-right tabular-nums text-gray-700">{{ $this->formatM3($row->curr_hot_m3) }}</td>
                                                <td class="py-3 pr-3 text-right tabular-nums text-gray-900">{{ $this->formatConsumption($row->curr_cold_m3, $row->prev_cold_m3) }}</td>
                                                <td class="py-3 pr-3 text-right tabular-nums text-gray-900">{{ $this->formatConsumption($row->curr_hot_m3, $row->prev_hot_m3) }}</td>
                                            @endif
                                            <td class="py-3 pl-2 text-right whitespace-nowrap">
                                                @if ($this->isEditingApartment($row->id))
                                                    <div class="flex flex-col sm:flex-row gap-2 justify-end items-stretch sm:items-center">
                                                        <x-primary-button type="button" class="text-xs px-2 py-1.5" wire:click="saveEditingApartment">{{ __('Сохранить') }}</x-primary-button>
                                                        <x-secondary-button type="button" class="text-xs px-2 py-1.5" wire:click="cancelEditApartment">{{ __('Отмена') }}</x-secondary-button>
                                                    </div>
                                                @else
                                                    <div class="flex flex-col sm:flex-row gap-2 justify-end items-stretch sm:items-center">
                                                        <x-secondary-button type="button" class="text-xs px-2 py-1.5" wire:click="startEditApartment({{ $row->id }})">{{ __('Редактировать') }}</x-secondary-button>
                                                        <a href="{{ route('manager.readings.apartment', ['apartment' => $row->id]) }}" wire:navigate class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-indigo-200 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">{{ __('История') }}</a>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="py-6 text-gray-500">{{ __('Нет квартир или ничего не найдено.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            {{ $this->rows->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
