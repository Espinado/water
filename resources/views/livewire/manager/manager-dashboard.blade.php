<div wire:poll.4s="pollSubmissionUpdates" class="manager-mobile-pad py-6 sm:py-8">
    <x-manager.submission-toast />

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="k16-page-title">{{ __('Главная') }}</h1>
            @if ($building_id && $this->buildings->isNotEmpty())
                <p class="mt-2 k16-page-subtitle">{{ __('Период: :period', ['period' => $this->periodLabel]) }}</p>
            @endif
        </div>

        <x-manager.context-bar
            :buildings="$this->buildings"
            :building-id="$building_id"
            year-model="statusYear"
            month-model="statusMonth"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->buildings->isEmpty())
            <div class="k16-card p-6 text-k16-text-muted">
                <p>{{ __('Нет домов. Добавьте первый дом в разделе «Дома».') }}</p>
                <a href="{{ route('manager.setup') }}" wire:navigate class="k16-btn-primary mt-4 inline-flex">
                    {{ __('Перейти к настройке') }}
                </a>
            </div>
        @elseif (! $building_id)
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Выберите дом.') }}</div>
        @else
            <div class="k16-card p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-base font-medium text-k16-text-muted">{{ __('Сдача показаний') }}</p>
                        <p class="mt-1 text-k16-display tabular-nums text-k16-text">
                            {{ $this->stats['submitted'] }} / {{ $this->stats['total'] }}
                        </p>
                    </div>
                    <div class="max-w-md flex-1">
                        <div class="h-4 overflow-hidden rounded-full bg-k16-bg">
                            <div
                                class="h-full rounded-full bg-k16-success transition-all duration-500"
                                style="width: {{ $this->submissionProgress }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-right text-base font-medium text-k16-text-muted">{{ $this->submissionProgress }}%</p>
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
                    :href="route('manager.apartments')"
                    :label="__('Всего квартир')"
                    :value="$this->stats['total']"
                    :hint="__('Полный список жильцов')"
                    tone="indigo"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('manager.apartments', ['filter' => 'debt']) }}" wire:navigate class="k16-btn-primary w-full text-center">
                    {{ __('Кто не сдал — список') }}
                </a>
                <a href="{{ route('manager.readings', ['filter' => 'debt']) }}" wire:navigate class="k16-btn-secondary w-full text-center">
                    {{ __('Ввести показания') }}
                </a>
            </div>
        @endif
    </div>
</div>
