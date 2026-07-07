<div>
    <div class="resident-mobile-pad py-6 sm:py-10 px-3 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">
            <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Личный кабинет</h1>

            @if (session('reading_status'))
                <div class="rounded-xl bg-green-50 px-4 py-3 text-sm leading-snug text-green-900 sm:px-5">{{ session('reading_status') }}</div>
            @endif
            @if (session('reading_error'))
                <div class="rounded-xl bg-red-50 px-4 py-3 text-sm leading-snug text-red-900 sm:px-5">{{ session('reading_error') }}</div>
            @endif
            @if (session('reading_ocr_hint'))
                <div class="rounded-xl bg-sky-50 px-4 py-3 text-sm leading-snug text-sky-950 sm:px-5">{{ session('reading_ocr_hint') }}</div>
            @endif

            @if (auth()->user()->isManager())
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                    <div class="p-4 text-gray-900 space-y-4 sm:p-6">
                        <p class="text-lg font-medium">Панель управляющего</p>
                        <p class="text-sm text-gray-600 leading-relaxed">Создавайте дома и квартиры, выдавайте доступ жильцам и просматривайте показания.</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <a href="{{ route('manager.panel') }}" wire:navigate class="inline-flex min-h-[48px] items-center justify-center rounded-xl bg-gray-800 px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 sm:inline-flex sm:min-h-0 sm:py-2">
                                Дома и доступ
                            </a>
                            <a href="{{ route('manager.apartments') }}" wire:navigate class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 sm:inline-flex sm:min-h-0 sm:py-2">
                                Квартиры
                            </a>
                            <a href="{{ route('manager.readings') }}" wire:navigate class="inline-flex min-h-[48px] items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50 sm:inline-flex sm:min-h-0 sm:py-2">
                                Показания по дому
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()->isResident())
                @if (! auth()->user()->apartment_id)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 shadow-sm overflow-hidden">
                        <div class="p-4 text-sm leading-relaxed text-amber-950 sm:p-6">
                            Вам ещё не назначена квартира. Обратитесь к управляющему дома.
                        </div>
                    </div>
                @else
                    @if (config('water.submission_window_bypass'))
                        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-950 sm:px-5">
                            Тестовый режим: ограничения по срокам сдачи показаний отключены ({{ config('app.env') }}).
                        </div>
                    @endif

                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                        <div class="p-4 text-gray-900 space-y-4 sm:p-6 sm:space-y-5">
                            <div class="space-y-2">
                                <h2 class="text-lg font-semibold text-gray-900 sm:text-xl">Показания счётчиков</h2>
                                <p class="text-sm leading-relaxed text-gray-600">
                                    Квартира <span class="font-medium text-gray-900">{{ $this->residentApartment->number }}</span>, {{ $this->residentApartment->building->name }}
                                </p>
                                <p class="text-sm leading-relaxed text-gray-600">
                                    Приём показаний жильцами: <strong>с {{ config('water.submission_opens_day') }}-го</strong> расчётного месяца
                                    по <strong>{{ config('water.submission_closes_day') }}-е</strong> следующего месяца включительно.
                                    В остальные дни вносит только управляющий.
                                </p>
                            </div>

                            @if ($this->residentPeriod)
                                <p class="text-sm leading-relaxed text-gray-800">
                                    Текущий расчётный период: <strong>{{ sprintf('%04d-%02d', $this->residentPeriod['year'], $this->residentPeriod['month']) }}</strong>
                                    (приём до {{ $this->residentPeriodCloseFormatted }}).
                                </p>

                                @if ($this->residentCanEditMeter)
                                    @if ($this->residentSubmittedForCurrentPeriod)
                                        <p class="text-sm font-medium leading-relaxed text-emerald-800">Показания сданы.</p>
                                    @else
                                        <form wire:submit="saveReading" class="mx-auto w-full max-w-full space-y-5 sm:max-w-md">
                                            <p class="text-sm leading-relaxed text-gray-600">Введите показания вручную или нажмите «Считать» — откроется камера. Снимите табло крупно, без бликов; распознанное значение подставится в поле, его можно поправить.</p>

                                            <div class="space-y-2">
                                                <x-input-label for="cold_m3" value="Холодная вода, м³" />
                                                <div class="flex items-stretch gap-2">
                                                    <x-text-input wire:model="cold_m3" id="cold_m3" type="text" inputmode="decimal" class="min-h-[48px] flex-1" required />
                                                    <label
                                                        for="coldMeterPhoto"
                                                        wire:loading.class="pointer-events-none opacity-60"
                                                        wire:target="coldMeterPhoto"
                                                        class="inline-flex min-h-[48px] shrink-0 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-sky-600 bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-sky-500"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                        <span wire:loading.remove wire:target="coldMeterPhoto">Считать</span>
                                                        <span wire:loading wire:target="coldMeterPhoto">…</span>
                                                    </label>
                                                    <input wire:model="coldMeterPhoto" id="coldMeterPhoto" type="file" accept="image/*" capture="environment" class="sr-only" />
                                                </div>
                                                <div wire:loading wire:target="coldMeterPhoto" class="text-xs text-sky-700">Распознаём фото ХВС…</div>
                                                <x-input-error :messages="$errors->get('cold_m3')" class="mt-1" />
                                                <x-input-error :messages="$errors->get('coldMeterPhoto')" class="mt-1" />
                                            </div>

                                            <div class="space-y-2">
                                                <x-input-label for="hot_m3" value="Горячая вода, м³" />
                                                <div class="flex items-stretch gap-2">
                                                    <x-text-input wire:model="hot_m3" id="hot_m3" type="text" inputmode="decimal" class="min-h-[48px] flex-1" required />
                                                    <label
                                                        for="hotMeterPhoto"
                                                        wire:loading.class="pointer-events-none opacity-60"
                                                        wire:target="hotMeterPhoto"
                                                        class="inline-flex min-h-[48px] shrink-0 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-rose-600 bg-rose-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-rose-500"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                        <span wire:loading.remove wire:target="hotMeterPhoto">Считать</span>
                                                        <span wire:loading wire:target="hotMeterPhoto">…</span>
                                                    </label>
                                                    <input wire:model="hotMeterPhoto" id="hotMeterPhoto" type="file" accept="image/*" capture="environment" class="sr-only" />
                                                </div>
                                                <div wire:loading wire:target="hotMeterPhoto" class="text-xs text-rose-700">Распознаём фото ГВС…</div>
                                                <x-input-error :messages="$errors->get('hot_m3')" class="mt-1" />
                                                <x-input-error :messages="$errors->get('hotMeterPhoto')" class="mt-1" />
                                            </div>
                                            <x-primary-button type="submit" class="!flex min-h-[48px] w-full justify-center sm:!inline-flex sm:w-auto sm:min-h-0">
                                                Сохранить показания
                                            </x-primary-button>
                                        </form>
                                    @endif
                                @else
                                    <p class="text-sm leading-relaxed text-amber-900">Срок сдачи по этому периоду истёк. Изменить показания может управляющий.</p>
                                @endif
                            @else
                                <p class="text-sm leading-relaxed text-gray-600">
                                    Сейчас окно для самостоятельной подачи закрыто (между {{ $this->submissionHintGap['from'] }}-м и {{ $this->submissionHintGap['to'] }}-м числом).
                                    Следующий приём откроется {{ $this->submissionHintGap['nextOpens'] }}-го числа.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                        <div class="p-4 sm:p-6">
                            <h2 class="text-lg font-semibold text-gray-900 sm:text-xl">История</h2>
                            <p class="mt-1 text-xs text-gray-500 sm:text-sm">На телефоне — карточки; на широком экране — таблица.</p>

                            <div class="mt-4">
                                <x-input-label for="history_search" value="Поиск по периоду (например, 2026-04)" />
                                <x-text-input wire:model.live.debounce.300ms="historySearch" id="history_search" type="search" class="mt-2 min-h-[48px]" placeholder="YYYY-MM" autocomplete="off" />
                            </div>

                            <div class="mt-4 sm:hidden">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Сортировка</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        wire:click="sortHistoryBy('period')"
                                        class="min-h-[44px] rounded-xl border border-gray-200 bg-white px-1 text-xs font-medium text-gray-800 active:bg-indigo-50"
                                    >
                                        Период @if ($historySortField === 'period'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="sortHistoryBy('cold')"
                                        class="min-h-[44px] rounded-xl border border-gray-200 bg-white px-1 text-xs font-medium text-gray-800 active:bg-indigo-50"
                                    >
                                        ХВС @if ($historySortField === 'cold'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="sortHistoryBy('hot')"
                                        class="min-h-[44px] rounded-xl border border-gray-200 bg-white px-1 text-xs font-medium text-gray-800 active:bg-indigo-50"
                                    >
                                        ГВС @if ($historySortField === 'hot'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3 sm:hidden">
                                @forelse ($this->readingHistoryRowsWithConsumption as $item)
                                    <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                                        <p class="text-base font-semibold text-indigo-950">{{ $item['row']->periodLabel() }}</p>
                                        <dl class="mt-3 space-y-2 text-base">
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-slate-500">ХВС, м³</dt>
                                                <dd class="font-semibold text-slate-900 tabular-nums">{{ $item['row']->cold_m3 }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-slate-500">ГВС, м³</dt>
                                                <dd class="font-semibold text-slate-900 tabular-nums">{{ $item['row']->hot_m3 }}</dd>
                                            </div>
                                            <div class="border-t border-indigo-50 pt-2 text-sm leading-snug text-slate-700">
                                                Расход за месяц: ХВС {{ $item['cold_consumption'] ?? '—' }}, ГВС {{ $item['hot_consumption'] ?? '—' }}
                                            </div>
                                        </dl>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-gray-500">Пока нет записей</div>
                                @endforelse
                            </div>
                            <div class="mt-4 hidden overflow-x-auto sm:block">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b text-left text-gray-600">
                                            <th class="pb-2 pr-4">
                                                <button type="button" wire:click="sortHistoryBy('period')" class="font-medium hover:text-indigo-600">
                                                    Период @if ($historySortField === 'period'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                                </button>
                                            </th>
                                            <th class="pb-2 pr-4">
                                                <button type="button" wire:click="sortHistoryBy('cold')" class="font-medium hover:text-indigo-600">
                                                    ХВС, м³ @if ($historySortField === 'cold'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                                </button>
                                            </th>
                                            <th class="pb-2 pr-4">
                                                <button type="button" wire:click="sortHistoryBy('hot')" class="font-medium hover:text-indigo-600">
                                                    ГВС, м³ @if ($historySortField === 'hot'){{ $historySortAsc ? '↑' : '↓' }}@endif
                                                </button>
                                            </th>
                                            <th class="pb-2">Расход за месяц, м³</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($this->readingHistoryRowsWithConsumption as $item)
                                            <tr class="border-b border-gray-100">
                                                <td class="py-2 pr-4">{{ $item['row']->periodLabel() }}</td>
                                                <td class="py-2 pr-4">{{ $item['row']->cold_m3 }}</td>
                                                <td class="py-2 pr-4">{{ $item['row']->hot_m3 }}</td>
                                                <td class="py-2">
                                                    ХВС: {{ $item['cold_consumption'] ?? '—' }}, ГВС: {{ $item['hot_consumption'] ?? '—' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="py-4 text-gray-500">Пока нет записей</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-6 flex justify-center overflow-x-auto [-webkit-overflow-scrolling:touch]">
                                <div class="inline-flex min-w-0 max-w-full">
                                    {{ $this->readingHistoryRows->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
