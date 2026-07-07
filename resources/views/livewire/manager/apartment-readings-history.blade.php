<div class="manager-mobile-pad py-6 sm:py-10">
    <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('manager.setup') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← {{ __('К дому') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ __('Показания квартиры') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $this->apartment->building->name }}, {{ __('кв. :number', ['number' => $this->apartment->number]) }}</p>

                @if ($this->resident)
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3 ring-1 ring-slate-100 sm:inline-block sm:min-w-[18rem]">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Жилец') }}</p>
                        <p class="mt-1 text-sm font-bold text-indigo-950">{{ $this->resident->last_name }} {{ $this->resident->first_name }}</p>
                        <p class="text-sm text-slate-600">{{ $this->resident->email }}</p>
                        <p class="text-sm text-slate-600">{{ $this->resident->phone ?: '—' }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if ($this->resident->last_login_at)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Входил') }}</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">{{ __('Не входил') }}</span>
                            @endif
                            @if ($this->resident->access_suspended_at)
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">{{ __('Доступ закрыт') }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-500">{{ __('Жилец не назначен') }}</p>
                @endif
            </div>
        </div>

        @if (session('reading_saved'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 ring-1 ring-emerald-100">{{ session('reading_saved') }}</div>
        @endif

        {{-- Ручной ввод показаний управляющим --}}
        <div class="app-card overflow-hidden">
            <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-sky-50 px-4 py-4 sm:px-6">
                <h2 class="text-lg font-bold text-indigo-950">{{ __('Ввод показаний') }}</h2>
                <p class="mt-0.5 text-sm text-slate-600">{{ __('Управляющий вносит показания за выбранный период. После сохранения повторная сдача жильцом за этот период закрывается.') }}</p>
            </div>

            <form wire:submit="saveEntry" class="space-y-4 p-4 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="entry-year" :value="__('Год')" />
                        <select wire:model.live="entryYear" id="entry-year" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @for ($y = (int) now()->year + 1; $y >= (int) now()->year - 6; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <x-input-label for="entry-month" :value="__('Месяц')" />
                        <select wire:model.live="entryMonth" id="entry-month" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @for ($mo = 1; $mo <= 12; $mo++)
                                <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="entry-cold" :value="__('ХВС, м³')" />
                        <x-text-input wire:model="entry_cold" id="entry-cold" type="text" inputmode="decimal" class="mt-1 block w-full rounded-xl text-right font-semibold" />
                        <x-input-error :messages="$errors->get('entry_cold')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="entry-hot" :value="__('ГВС, м³')" />
                        <x-text-input wire:model="entry_hot" id="entry-hot" type="text" inputmode="decimal" class="mt-1 block w-full rounded-xl text-right font-semibold" />
                        <x-input-error :messages="$errors->get('entry_hot')" class="mt-1" />
                    </div>
                </div>

                <button type="submit" class="inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-emerald-200 hover:from-emerald-600 hover:to-emerald-700 sm:w-auto">
                    <span wire:loading.remove wire:target="saveEntry">{{ __('Сохранить показания') }}</span>
                    <span wire:loading wire:target="saveEntry">{{ __('Сохранение…') }}</span>
                </button>
            </form>
        </div>

        <div class="app-card overflow-hidden">
            <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 to-indigo-50 px-4 py-4 sm:px-6">
                <h2 class="text-lg font-bold text-indigo-950">{{ __('История по месяцам') }}</h2>
            </div>

            <div class="space-y-4 border-b border-slate-100 p-4 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="filter-year" :value="__('Год')" />
                        <select
                            wire:model.live="filterYear"
                            id="filter-year"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach ($this->availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter-month" :value="__('Месяц')" />
                        <select
                            wire:model.live="filterMonth"
                            id="filter-month"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @for ($mo = 1; $mo <= 12; $mo++)
                                <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-3 p-4 sm:hidden">
                @forelse ($this->rows as $row)
                    <div wire:key="read-mobile-{{ $row->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
                        <p class="text-sm font-bold text-indigo-950">{{ $this->periodLabel($row) }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div><dt class="text-slate-500">{{ __('ХВС') }}</dt><dd class="font-semibold tabular-nums">{{ $row->cold_m3 }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('Расход ХВС') }}</dt><dd class="font-semibold tabular-nums text-sky-800">{{ $this->consumptionFor($row, 'cold') }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('ГВС') }}</dt><dd class="font-semibold tabular-nums">{{ $row->hot_m3 }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('Расход ГВС') }}</dt><dd class="font-semibold tabular-nums text-sky-800">{{ $this->consumptionFor($row, 'hot') }}</dd></div>
                        </dl>
                    </div>
                @empty
                    <p class="py-6 text-center text-slate-500">{{ __('Показаний за выбранный период нет.') }}</p>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto p-4 sm:block sm:p-6 sm:pt-0">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-600">
                            <th class="pb-3 pr-4">
                                <button type="button" wire:click="sortBy('period')" class="font-semibold hover:text-indigo-700">
                                    {{ __('Период') }} @if ($sortField === 'period'){{ $sortAsc ? '↑' : '↓' }}@endif
                                </button>
                            </th>
                            <th class="pb-3 pr-4 text-right">
                                <button type="button" wire:click="sortBy('cold')" class="font-semibold hover:text-indigo-700">
                                    {{ __('ХВС, м³') }} @if ($sortField === 'cold'){{ $sortAsc ? '↑' : '↓' }}@endif
                                </button>
                            </th>
                            <th class="pb-3 pr-4 text-right font-semibold">{{ __('Расход ХВС, м³') }}</th>
                            <th class="pb-3 pr-4 text-right">
                                <button type="button" wire:click="sortBy('hot')" class="font-semibold hover:text-indigo-700">
                                    {{ __('ГВС, м³') }} @if ($sortField === 'hot'){{ $sortAsc ? '↑' : '↓' }}@endif
                                </button>
                            </th>
                            <th class="pb-3 text-right font-semibold">{{ __('Расход ГВС, м³') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->rows as $row)
                            <tr wire:key="read-desktop-{{ $row->id }}">
                                <td class="py-3 pr-4 font-medium text-indigo-950">{{ $this->periodLabel($row) }}</td>
                                <td class="py-3 pr-4 text-right tabular-nums">{{ $row->cold_m3 }}</td>
                                <td class="py-3 pr-4 text-right tabular-nums font-medium text-sky-800">{{ $this->consumptionFor($row, 'cold') }}</td>
                                <td class="py-3 pr-4 text-right tabular-nums">{{ $row->hot_m3 }}</td>
                                <td class="py-3 text-right tabular-nums font-medium text-sky-800">{{ $this->consumptionFor($row, 'hot') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">{{ __('Показаний за выбранный период нет.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->rows->hasPages())
                <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                    {{ $this->rows->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
