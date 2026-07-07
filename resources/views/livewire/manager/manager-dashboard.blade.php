<div wire:poll.4s="pollSubmissionUpdates" class="manager-mobile-pad py-6 sm:py-10">
    <x-manager.submission-toast />

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">{{ __('Панель управляющего') }}</p>
                <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ __('Обзор') }}</h1>
                @if ($building_id && $this->buildings->isNotEmpty())
                    <p class="mt-2 text-sm text-slate-600">{{ __('Период: :period', ['period' => $this->periodLabel]) }}</p>
                @endif
            </div>
            <a
                href="{{ route('manager.setup') }}"
                wire:navigate
                class="inline-flex min-h-[44px] items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
            >
                {{ __('Настройка домов') }}
            </a>
        </div>

        <x-manager.context-bar
            :buildings="$this->buildings"
            :building-id="$building_id"
            year-model="statusYear"
            month-model="statusMonth"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->buildings->isEmpty())
            <div class="app-card p-6 text-slate-600">
                <p>{{ __('Нет домов. Добавьте первый дом в настройках.') }}</p>
                <a href="{{ route('manager.setup') }}" wire:navigate class="mt-4 inline-flex text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                    {{ __('Перейти к настройке') }} →
                </a>
            </div>
        @elseif (! $building_id)
            <div class="app-card p-6 text-slate-600">{{ __('Выберите дом.') }}</div>
        @else
            <div class="app-card overflow-hidden p-4 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">{{ __('Сдача показаний') }}</p>
                        <p class="text-2xl font-bold text-indigo-950">
                            {{ $this->stats['submitted'] }} / {{ $this->stats['total'] }}
                        </p>
                    </div>
                    <div class="flex-1 max-w-md">
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500"
                                style="width: {{ $this->submissionProgress }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-right text-xs font-medium text-slate-500">{{ $this->submissionProgress }}%</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <x-manager.stat-card
                    :href="route('manager.apartments', ['filter' => 'debt'])"
                    :label="__('Не сдали')"
                    :value="$this->stats['debt']"
                    :hint="__('Открыть список долга')"
                    tone="rose"
                />
                <x-manager.stat-card
                    :href="route('manager.apartments', ['filter' => 'submitted'])"
                    :label="__('Сдали')"
                    :value="$this->stats['submitted']"
                    :hint="__('Квартиры с показаниями')"
                    tone="emerald"
                />
                <x-manager.stat-card
                    :href="route('manager.readings', ['filter' => 'debt'])"
                    :label="__('Ввести показания')"
                    :value="$this->stats['debt']"
                    :hint="__('Таблица ввода для должников')"
                    tone="sky"
                />
                <x-manager.stat-card
                    :href="route('manager.apartments', ['filter' => 'no_login'])"
                    :label="__('Не входили')"
                    :value="$this->stats['no_login']"
                    :hint="__('Жильцы без входа в систему')"
                    tone="amber"
                />
                <x-manager.stat-card
                    :href="route('manager.apartments', ['filter' => 'no_resident'])"
                    :label="__('Без жильца')"
                    :value="$this->stats['no_resident']"
                    :hint="__('Нужно создать доступ')"
                    tone="violet"
                />
                <x-manager.stat-card
                    :label="__('Всего квартир')"
                    :value="$this->stats['total']"
                    tone="indigo"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a
                    href="{{ route('manager.apartments', ['filter' => 'debt']) }}"
                    wire:navigate
                    class="flex min-h-[56px] items-center justify-center rounded-2xl bg-gradient-to-r from-rose-500 to-rose-600 px-5 py-4 text-center text-sm font-bold text-white shadow-lg shadow-rose-200 transition hover:from-rose-600 hover:to-rose-700"
                >
                    {{ __('Кто не сдал — список') }}
                </a>
                <a
                    href="{{ route('manager.readings', ['filter' => 'debt']) }}"
                    wire:navigate
                    class="flex min-h-[56px] items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-4 text-center text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:from-sky-600 hover:to-indigo-700"
                >
                    {{ __('Ввести показания') }}
                </a>
            </div>
        @endif
    </div>
</div>
