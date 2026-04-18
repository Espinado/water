<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-2xl font-semibold text-gray-900 px-4 sm:px-0">Личный кабинет</h1>

            @if (session('reading_status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 mx-4 sm:mx-0">{{ session('reading_status') }}</div>
            @endif
            @if (session('reading_error'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mx-4 sm:mx-0">{{ session('reading_error') }}</div>
            @endif
            @if (session('reading_ocr_hint'))
                <div class="rounded-md bg-sky-50 p-4 text-sm text-sky-900 mx-4 sm:mx-0">{{ session('reading_ocr_hint') }}</div>
            @endif

            @if (auth()->user()->isManager())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 space-y-4">
                        <p class="text-lg font-medium">Панель управляющего</p>
                        <p class="text-sm text-gray-600">Создавайте дома и квартиры, выдавайте доступ жильцам и просматривайте показания.</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('manager.panel') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Дома и доступ
                            </a>
                            <a href="{{ route('manager.apartments') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Квартиры
                            </a>
                            <a href="{{ route('manager.readings') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Показания по дому
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()->isResident())
                @if (! auth()->user()->apartment_id)
                    <div class="bg-amber-50 border border-amber-200 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-amber-900 text-sm">
                            Вам ещё не назначена квартира. Обратитесь к управляющему дома.
                        </div>
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 space-y-4">
                            <div>
                                <h3 class="text-lg font-medium">Показания счётчиков</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Квартира {{ $this->residentApartment->number }}, {{ $this->residentApartment->building->name }}
                                </p>
                                <p class="mt-2 text-sm text-gray-600">
                                    Приём показаний жильцами: <strong>с {{ config('water.submission_opens_day') }}-го</strong> расчётного месяца
                                    по <strong>{{ config('water.submission_closes_day') }}-е</strong> следующего месяца включительно.
                                    В остальные дни вносит только управляющий.
                                </p>
                            </div>

                            @if ($this->residentPeriod)
                                <p class="text-sm">
                                    Текущий расчётный период: <strong>{{ sprintf('%04d-%02d', $this->residentPeriod['year'], $this->residentPeriod['month']) }}</strong>
                                    (приём до {{ $this->residentPeriodCloseFormatted }}).
                                </p>

                                @if ($this->residentCanEditMeter)
                                    @if ($this->residentSubmittedForCurrentPeriod)
                                        <p class="text-sm text-emerald-800 font-medium">Показания сданы.</p>
                                    @else
                                        <form wire:submit="saveReading" class="space-y-4 max-w-md">
                                            <div>
                                                <x-input-label for="cold_m3" value="Холодная вода, м³" />
                                                <x-text-input wire:model="cold_m3" id="cold_m3" type="text" inputmode="decimal" class="block mt-1 w-full" required />
                                                <x-input-error :messages="$errors->get('cold_m3')" class="mt-2" />
                                            </div>
                                            <div>
                                                <x-input-label for="hot_m3" value="Горячая вода, м³" />
                                                <x-text-input wire:model="hot_m3" id="hot_m3" type="text" inputmode="decimal" class="block mt-1 w-full" required />
                                                <x-input-error :messages="$errors->get('hot_m3')" class="mt-2" />
                                            </div>
                                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                                                <p class="text-sm font-medium text-gray-800">Распознать с фото</p>
                                                <p class="text-xs text-gray-600">Сфотографируйте табло счётчиков (оба в кадре — лучше по порядку слева направо). После распознавания проверьте цифры перед сохранением.</p>
                                                <div>
                                                    <input wire:model="meterPhoto" id="meter_photo" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-500" />
                                                    <x-input-error :messages="$errors->get('meterPhoto')" class="mt-2" />
                                                </div>
                                                <div wire:loading wire:target="meterPhoto" class="text-xs text-gray-500">Загрузка файла…</div>
                                                <button type="button" wire:click="recognizeMeterFromPhoto" wire:loading.attr="disabled" wire:target="meterPhoto,recognizeMeterFromPhoto" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="recognizeMeterFromPhoto">Распознать</span>
                                                    <span wire:loading wire:target="recognizeMeterFromPhoto">Запрос к Vision…</span>
                                                </button>
                                            </div>
                                            <x-primary-button type="submit">Сохранить показания</x-primary-button>
                                        </form>
                                    @endif
                                @else
                                    <p class="text-sm text-amber-800">Срок сдачи по этому периоду истёк. Изменить показания может управляющий.</p>
                                @endif
                            @else
                                <p class="text-sm text-gray-600">
                                    Сейчас окно для самостоятельной подачи закрыто (между {{ $this->submissionHintGap['from'] }}-м и {{ $this->submissionHintGap['to'] }}-м числом).
                                    Следующий приём откроется {{ $this->submissionHintGap['nextOpens'] }}-го числа.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">История</h3>
                            <div class="mb-4 max-w-sm">
                                <x-input-label for="history_search" value="Поиск по периоду (например, 2026-04)" />
                                <x-text-input wire:model.live.debounce.300ms="historySearch" id="history_search" type="search" class="mt-1 block w-full" placeholder="YYYY-MM" />
                            </div>
                            <div class="space-y-3 sm:hidden">
                                @forelse ($this->readingHistoryRowsWithConsumption as $item)
                                    <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-indigo-900">{{ $item['row']->periodLabel() }}</p>
                                        </div>
                                        <dl class="mt-3 space-y-1 text-sm">
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">ХВС</dt>
                                                <dd class="font-medium text-slate-800">{{ $item['row']->cold_m3 }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">ГВС</dt>
                                                <dd class="font-medium text-slate-800">{{ $item['row']->hot_m3 }}</dd>
                                            </div>
                                            <div class="pt-1 text-slate-700">
                                                Расход: ХВС {{ $item['cold_consumption'] ?? '—' }}, ГВС {{ $item['hot_consumption'] ?? '—' }}
                                            </div>
                                        </dl>
                                    </div>
                                @empty
                                    <div class="py-4 text-gray-500">Пока нет записей</div>
                                @endforelse
                            </div>
                            <div class="hidden overflow-x-auto sm:block">
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
                            <div class="mt-4">
                                {{ $this->readingHistoryRows->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
