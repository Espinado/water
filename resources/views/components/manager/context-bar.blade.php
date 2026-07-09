@props([
    'buildings',
    'buildingId' => null,
    'yearModel' => 'statusYear',
    'monthModel' => 'statusMonth',
    'periodTitle' => null,
    'lockedPeriodLabel' => null,
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
                            'k16-building-tab',
                            'k16-building-tab-active' => (int) $buildingId === (int) $b->id,
                            'k16-building-tab-idle' => (int) $buildingId !== (int) $b->id,
                        ])
                    >
                        {{ $b->name }}
                        <span @class([
                            'ms-1 font-normal',
                            'text-white/80' => (int) $buildingId === (int) $b->id,
                            'text-k16-text-muted' => (int) $buildingId !== (int) $b->id,
                        ])>({{ $b->apartments_count }})</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="k16-card p-4 sm:p-5">
        <p class="text-base font-semibold text-k16-text">
            {{ $periodTitle ?? __('Расчётный период') }}
        </p>
        @if ($lockedPeriodLabel)
            <p class="mt-2 text-k16-body leading-relaxed text-k16-text-muted">
                {!! __('Сейчас приём показаний за <strong>:period</strong> — тот же период, что у жильца.', ['period' => $lockedPeriodLabel]) !!}
            </p>
        @else
            <div class="mt-3 flex flex-wrap gap-3">
                <div class="w-28">
                    <x-input-label :for="'mgr-year-'.$yearModel" :value="__('Год')" />
                    <x-text-input wire:model.live="{{ $yearModel }}" :id="'mgr-year-'.$yearModel" type="number" class="mt-1 block w-full" min="2000" max="2100" />
                </div>
                <div class="min-w-[10rem] flex-1 sm:max-w-xs">
                    <x-input-label :for="'mgr-month-'.$monthModel" :value="__('Месяц')" />
                    <select wire:model.live="{{ $monthModel }}" :id="'mgr-month-'.$monthModel" class="mt-1 block w-full rounded-xl border-k16-border text-k16-body shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                        @for ($mo = 1; $mo <= 12; $mo++)
                            <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        @endif
    </div>
</div>
