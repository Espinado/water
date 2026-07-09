<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <a href="{{ route('manager.readings') }}" wire:navigate class="text-base font-semibold text-k16-accent">
                ← {{ __('Показания') }}
            </a>
            <h1 class="mt-2 k16-page-title">{{ __('Показания квартиры') }}</h1>
            <p class="mt-1 k16-page-subtitle">
                {{ $this->apartment->building->name }}, {{ __('кв. :number', ['number' => $this->apartment->number]) }}
            </p>

            @if ($this->resident)
                <div class="k16-card mt-4 p-4">
                    <p class="text-base font-semibold text-k16-text-muted">{{ __('Жилец') }}</p>
                    <p class="mt-1 text-k16-lead font-bold text-k16-text">{{ $this->resident->last_name }} {{ $this->resident->first_name }}</p>
                    <p class="text-k16-body text-k16-text-muted">{{ $this->resident->email }}</p>
                    <p class="text-k16-body text-k16-text-muted">{{ $this->resident->phone ?: '—' }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if ($this->resident->last_login_at)
                            <span class="k16-badge-success">{{ __('Входил') }}</span>
                        @else
                            <span class="k16-badge-warning">{{ __('Не входил') }}</span>
                        @endif
                        @if ($this->resident->access_suspended_at)
                            <span class="k16-badge-danger">{{ __('Доступ закрыт') }}</span>
                        @endif
                    </div>
                </div>
            @else
                <p class="mt-3 text-k16-body text-k16-text-muted">{{ __('Жилец не назначен') }}</p>
            @endif
        </div>

        @if (session('reading_saved'))
            <div class="k16-alert-success">{{ session('reading_saved') }}</div>
        @endif
        @if (session('reading_error'))
            <div class="k16-alert-danger">{{ session('reading_error') }}</div>
        @endif

        <section class="k16-card overflow-hidden">
            <div class="border-b border-k16-border px-5 py-4">
                <h2 class="text-k16-lead font-bold text-k16-text">{{ __('Ввод показаний') }}</h2>
                <p class="mt-1 text-k16-body text-k16-text-muted">{{ __('Управляющий вносит показания за выбранный период. После сохранения повторная сдача жильцом за этот период закрывается.') }}</p>
            </div>

            @if ($this->entryAlreadySubmitted)
                <div class="space-y-3 p-5">
                    <div class="k16-alert-success">
                        @if ($this->entryLockedPeriodLabel)
                            {!! __('Показания за <strong>:period</strong> уже внесены. Форма ввода закрыта.', ['period' => $this->entryLockedPeriodLabel]) !!}
                        @else
                            {{ __('Показания за этот период уже внесены. Форма ввода закрыта.') }}
                        @endif
                    </div>
                    <p class="text-k16-body text-k16-text-muted">{{ __('Чтобы изменить значения — нажмите «Редактировать» в истории ниже.') }}</p>
                </div>
            @else
                <form wire:submit="saveEntry" class="space-y-4 p-5">
                    @if ($this->entryLockedPeriodLabel)
                        <p class="text-k16-body text-k16-text-muted">
                            {!! __('Период ввода: <strong>:period</strong> — тот же, что у жильца.', ['period' => $this->entryLockedPeriodLabel]) !!}
                        </p>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="entry-year" :value="__('Год')" />
                                <select wire:model.live="entryYear" id="entry-year" class="mt-1 block w-full rounded-xl">
                                    @for ($y = (int) now()->year + 1; $y >= (int) now()->year - 6; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <x-input-label for="entry-month" :value="__('Месяц')" />
                                <select wire:model.live="entryMonth" id="entry-month" class="mt-1 block w-full rounded-xl">
                                    @for ($mo = 1; $mo <= 12; $mo++)
                                        <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="entry-cold" :value="__('ХВС, м³')" />
                            <x-text-input wire:model="entry_cold" id="entry-cold" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('entry_cold')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="entry-hot" :value="__('ГВС, м³')" />
                            <x-text-input wire:model="entry_hot" id="entry-hot" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                            <x-input-error :messages="$errors->get('entry_hot')" class="mt-1" />
                        </div>
                    </div>

                    <button type="submit" class="k16-btn-primary w-full sm:w-auto">
                        <span wire:loading.remove wire:target="saveEntry">{{ __('Сохранить показания') }}</span>
                        <span wire:loading wire:target="saveEntry">{{ __('Сохранение…') }}</span>
                    </button>
                </form>
            @endif
        </section>

        <section class="k16-card overflow-hidden">
            <div class="border-b border-k16-border px-5 py-4">
                <h2 class="text-k16-lead font-bold text-k16-text">{{ __('История по месяцам') }}</h2>
            </div>

            <div class="border-b border-k16-border p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="filter-year" :value="__('Год')" />
                        <select wire:model.live="filterYear" id="filter-year" class="mt-1 block w-full rounded-xl">
                            @foreach ($this->availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter-month" :value="__('Месяц')" />
                        <select wire:model.live="filterMonth" id="filter-month" class="mt-1 block w-full rounded-xl">
                            @for ($mo = 1; $mo <= 12; $mo++)
                                <option value="{{ $mo }}">{{ \Carbon\Carbon::create(null, $mo, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-3 p-5">
                @forelse ($this->rows as $row)
                    <div wire:key="read-history-{{ $row->id }}" class="k16-card p-5">
                        <p class="text-k16-lead font-bold text-k16-text">{{ $this->periodLabel($row) }}</p>

                        @if ($this->isEditing($row->id))
                            <div class="mt-4 space-y-3">
                                <div>
                                    <x-input-label :for="'edit-cold-'.$row->id" :value="__('ХВС, м³')" />
                                    <x-text-input wire:model="edit_cold" :id="'edit-cold-'.$row->id" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                                    <x-input-error :messages="$errors->get('edit_cold')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label :for="'edit-hot-'.$row->id" :value="__('ГВС, м³')" />
                                    <x-text-input wire:model="edit_hot" :id="'edit-hot-'.$row->id" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" />
                                    <x-input-error :messages="$errors->get('edit_hot')" class="mt-1" />
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <button type="button" wire:click="saveEdit" class="k16-btn-primary flex-1">{{ __('Сохранить') }}</button>
                                    <button type="button" wire:click="cancelEdit" class="k16-btn-secondary flex-1">{{ __('Отмена') }}</button>
                                </div>
                            </div>
                        @else
                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-k16-body">
                                <div><dt class="text-k16-text-muted">{{ __('ХВС') }}</dt><dd class="font-semibold tabular-nums">{{ $row->cold_m3 }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Расход ХВС') }}</dt><dd class="font-semibold tabular-nums text-k16-accent">{{ $this->consumptionFor($row, 'cold') }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('ГВС') }}</dt><dd class="font-semibold tabular-nums">{{ $row->hot_m3 }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('Расход ГВС') }}</dt><dd class="font-semibold tabular-nums text-k16-accent">{{ $this->consumptionFor($row, 'hot') }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('ХВС, €') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatCost($row->cold_cost) }}</dd></div>
                                <div><dt class="text-k16-text-muted">{{ __('ГВС, €') }}</dt><dd class="font-semibold tabular-nums">{{ $this->formatCost($row->hot_cost) }}</dd></div>
                            </dl>
                            <button type="button" wire:click="startEdit({{ $row->id }})" class="k16-btn-secondary mt-4 w-full sm:w-auto">
                                {{ __('Редактировать') }}
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-center text-k16-body text-k16-text-muted">{{ __('Показаний за выбранный период нет.') }}</p>
                @endforelse
            </div>

            @if ($this->rows->hasPages())
                <div class="border-t border-k16-border px-5 py-4">
                    {{ $this->rows->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
