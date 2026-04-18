<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-4 sm:px-0">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-indigo-900">История показаний квартиры</h1>
                    <p class="text-base text-slate-700 mt-1">{{ $this->apartment->building->name }}, кв. {{ $this->apartment->number }}</p>
                </div>
                <a href="{{ route('manager.readings') }}" wire:navigate class="text-base font-semibold text-indigo-700 hover:text-indigo-900">← К таблице показаний</a>
            </div>

            <div class="app-card mx-4 sm:mx-0 overflow-hidden p-6 space-y-4">
                <div class="max-w-sm">
                    <x-input-label for="search" value="Поиск по периоду (YYYY-MM)" />
                    <x-text-input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full" placeholder="2026-04" />
                </div>

                <div class="space-y-3 sm:hidden">
                    @forelse ($this->rows as $row)
                        <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm">
                            <p class="text-sm font-semibold text-indigo-900">{{ $row->periodLabel() }}</p>
                            <dl class="mt-3 space-y-1 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">ХВС</dt><dd class="font-medium">{{ $row->cold_m3 }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Расход ХВС</dt><dd class="font-medium">{{ $this->consumptionFor($row, 'cold') }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">ГВС</dt><dd class="font-medium">{{ $row->hot_m3 }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Расход ГВС</dt><dd class="font-medium">{{ $this->consumptionFor($row, 'hot') }}</dd></div>
                            </dl>
                        </div>
                    @empty
                        <div class="py-6 text-gray-500">Показаний пока нет.</div>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-base">
                        <thead>
                            <tr class="border-b text-left text-gray-600">
                                <th class="pb-2 pr-4">
                                    <button type="button" wire:click="sortBy('period')" class="font-medium hover:text-indigo-600">
                                        Период @if ($sortField === 'period'){{ $sortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                </th>
                                <th class="pb-2 pr-4">
                                    <button type="button" wire:click="sortBy('cold')" class="font-medium hover:text-indigo-600">
                                        ХВС, м³ @if ($sortField === 'cold'){{ $sortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                </th>
                                <th class="pb-2 pr-4">Расход ХВС, м³</th>
                                <th class="pb-2 pr-4">
                                    <button type="button" wire:click="sortBy('hot')" class="font-medium hover:text-indigo-600">
                                        ГВС, м³ @if ($sortField === 'hot'){{ $sortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                </th>
                                <th class="pb-2">Расход ГВС, м³</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->rows as $row)
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 pr-4">{{ $row->periodLabel() }}</td>
                                    <td class="py-2 pr-4">{{ $row->cold_m3 }}</td>
                                    <td class="py-2 pr-4">{{ $this->consumptionFor($row, 'cold') }}</td>
                                    <td class="py-2 pr-4">{{ $row->hot_m3 }}</td>
                                    <td class="py-2">{{ $this->consumptionFor($row, 'hot') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-gray-500">Показаний пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $this->rows->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
