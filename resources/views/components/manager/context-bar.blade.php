@props([
    'buildings',
    'buildingId' => null,
    'yearModel' => 'statusYear',
    'monthModel' => 'statusMonth',
    'periodTitle' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if ($buildings->isNotEmpty())
        <div class="overflow-x-auto -mx-1 px-1 pb-1">
            <div class="flex gap-2 min-w-max" role="tablist" aria-label="{{ __('Дома') }}">
                @foreach ($buildings as $b)
                    <button
                        type="button"
                        wire:click="$set('building_id', {{ $b->id }})"
                        wire:key="mgr-building-{{ $b->id }}"
                        role="tab"
                        @class([
                            'manager-building-tab whitespace-nowrap rounded-2xl px-4 py-2.5 text-sm font-semibold transition min-h-[44px]',
                            'bg-indigo-600 text-white shadow-md shadow-indigo-200' => (int) $buildingId === (int) $b->id,
                            'bg-white/90 text-slate-700 ring-1 ring-slate-200 hover:bg-indigo-50 hover:text-indigo-800' => (int) $buildingId !== (int) $b->id,
                        ])
                    >
                        {{ $b->name }}
                        <span @class([
                            'ms-1 font-normal',
                            'text-indigo-100' => (int) $buildingId === (int) $b->id,
                            'text-slate-400' => (int) $buildingId !== (int) $b->id,
                        ])>({{ $b->apartments_count }})</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm ring-1 ring-slate-100 sm:p-5">
        <p class="text-sm font-semibold text-indigo-900">
            {{ $periodTitle ?? __('Расчётный период') }}
        </p>
        <div class="mt-3 flex flex-wrap gap-3">
            <div class="w-28">
                <x-input-label :for="'mgr-year-'.$yearModel" :value="__('Год')" class="text-xs" />
                <x-text-input wire:model.live="{{ $yearModel }}" :id="'mgr-year-'.$yearModel" type="number" class="mt-1 block w-full" min="2000" max="2100" />
            </div>
            <div class="min-w-[10rem] flex-1 sm:max-w-xs">
                <x-input-label :for="'mgr-month-'.$monthModel" :value="__('Месяц')" class="text-xs" />
                <select wire:model.live="{{ $monthModel }}" :id="'mgr-month-'.$monthModel" class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @for ($mo = 1; $mo <= 12; $mo++)
                        <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>
</div>
