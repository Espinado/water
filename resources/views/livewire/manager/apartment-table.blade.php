<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col gap-4 px-4 sm:px-0 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-3xl sm:text-4xl font-bold text-indigo-900">{{ __('Квартиры') }}</h1>
                <div class="flex flex-wrap gap-4 text-base">
                    <a href="{{ route('manager.panel') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Дома и доступ') }}</a>
                    <a href="{{ route('manager.readings') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Показания') }}</a>
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Кабинет') }}</a>
                </div>
            </div>

            @if (session('apt_ok'))
                <div class="mx-4 rounded-xl bg-emerald-50 p-4 text-base text-emerald-900 sm:mx-0">{{ session('apt_ok') }}</div>
            @endif
            @if (session('apt_err'))
                <div class="mx-4 rounded-xl bg-rose-50 p-4 text-base text-rose-900 sm:mx-0">{{ session('apt_err') }}</div>
            @endif

            <div class="mx-4 overflow-hidden app-card sm:mx-0">
                @if ($this->buildings->isNotEmpty())
                    <div class="border-b border-gray-200 px-4 pt-4 sm:px-6">
                        <nav class="-mb-px flex flex-wrap gap-1 sm:gap-2" aria-label="{{ __('Дома') }}">
                            @foreach ($this->buildings as $b)
                                <button
                                    type="button"
                                    wire:click="$set('building_id', {{ $b->id }})"
                                    wire:key="apt-tab-{{ $b->id }}"
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
                    </div>
                @endif

                <div class="space-y-4 border-b border-gray-100 p-4 sm:p-6">
                    <p class="text-base font-semibold text-indigo-900">{{ __('Период для колонки «Показания»') }}</p>
                    <div class="flex flex-wrap gap-4">
                        <div class="w-28">
                            <x-input-label for="sy" :value="__('Год')" />
                            <x-text-input wire:model.live="statusYear" id="sy" type="number" class="mt-1 block w-full" min="2000" max="2100" />
                        </div>
                        <div class="w-36">
                            <x-input-label for="sm" :value="__('Месяц')" />
                            <select wire:model.live="statusMonth" id="sm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @for ($mo = 1; $mo <= 12; $mo++)
                                    <option value="{{ $mo }}">{{ $mo }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="max-w-md">
                        <x-input-label for="search" :value="__('Поиск')" />
                        <x-text-input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full" :placeholder="__('Квартира, ФИО, email, телефон…')" />
                    </div>
                </div>

                @if ($this->buildings->isEmpty())
                    <div class="p-6 text-sm text-gray-500">{{ __('Нет домов. Создайте в разделе «Дома и доступ».') }}</div>
                @elseif (! $building_id)
                    <div class="p-6 text-sm text-gray-500">{{ __('Выберите дом во вкладке.') }}</div>
                @else
                    <div class="space-y-3 p-4 sm:hidden">
                        @forelse ($this->rows as $apt)
                            <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm" wire:key="apt-mobile-{{ $apt->id }}">
                                <div class="flex items-center justify-between">
                                    <p class="text-base font-semibold text-indigo-900">{{ __('Кв. :number', ['number' => $apt->number]) }}</p>
                                    @if ($apt->period_meter_reading_id)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Сданы') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ __('Долг') }}</span>
                                    @endif
                                </div>
                                <dl class="mt-3 space-y-1 text-sm">
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Имя') }}</dt><dd>{{ $this->residentDisplayFirst($apt) }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Фамилия') }}</dt><dd>{{ $this->residentDisplayLast($apt) }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Телефон') }}</dt><dd>{{ $apt->ru_phone ?: '—' }}</dd></div>
                                    <div class="break-all"><span class="text-slate-500">{{ __('Почта:') }}</span> {{ $apt->ru_email ?: '—' }}</div>
                                    <div class="pt-1">
                                        @if ($apt->resident_user_id)
                                            @if ($apt->ru_invitation_sent_at)
                                                <span class="text-xs text-gray-500">{{ __('Отпр. :date', ['date' => $this->formatInvitationDate($apt->ru_invitation_sent_at)]) }}</span>
                                            @else
                                                <span class="text-xs text-amber-700">{{ __('Ссылка не отправлялась') }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </dl>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($apt->resident_user_id)
                                        <button type="button" wire:click="sendInvitation({{ (int) $apt->resident_user_id }})" wire:loading.attr="disabled" class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-indigo-200 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">{{ __('Ссылка на пароль') }}</button>
                                        @if ($apt->ru_access_suspended_at)
                                            <button type="button" wire:click="toggleAccess({{ (int) $apt->resident_user_id }})" class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-emerald-200 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">{{ __('Открыть доступ') }}</button>
                                        @else
                                            <button type="button" wire:click="toggleAccess({{ (int) $apt->resident_user_id }})" class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50">{{ __('Закрыть доступ') }}</button>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-500">{{ __('Создайте жильца в «Дома и доступ»') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-1 py-6 text-gray-500">{{ __('Нет квартир или ничего не найдено.') }}</div>
                        @endforelse
                    </div>
                    <div class="hidden overflow-x-auto sm:block">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-base">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">
                                        <button type="button" wire:click="sortBy('number')" class="hover:text-indigo-600">
                                            {{ __('Кв.') }} @if ($sortField === 'number'){{ $sortAsc ? '↑' : '↓' }}@endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">
                                        <button type="button" wire:click="sortBy('first_name')" class="hover:text-indigo-600">
                                            {{ __('Имя') }} @if ($sortField === 'first_name'){{ $sortAsc ? '↑' : '↓' }}@endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">
                                        <button type="button" wire:click="sortBy('last_name')" class="hover:text-indigo-600">
                                            {{ __('Фамилия') }} @if ($sortField === 'last_name'){{ $sortAsc ? '↑' : '↓' }}@endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">
                                        <button type="button" wire:click="sortBy('phone')" class="hover:text-indigo-600">
                                            {{ __('Телефон') }} @if ($sortField === 'phone'){{ $sortAsc ? '↑' : '↓' }}@endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">
                                        <button type="button" wire:click="sortBy('email')" class="hover:text-indigo-600">
                                            {{ __('Почта') }} @if ($sortField === 'email'){{ $sortAsc ? '↑' : '↓' }}@endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">{{ __('Показания') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">{{ __('Пароль (ссылка)') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">{{ __('Вошёл в систему') }}</th>
                                    <th class="px-3 py-3 font-medium text-gray-700 sm:px-4">{{ __('Доступ') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($this->rows as $apt)
                                    <tr wire:key="row-{{ $apt->id }}" class="align-top">
                                        <td class="px-3 py-3 font-medium text-gray-900 sm:px-4">{{ $apt->number }}</td>
                                        <td class="px-3 py-3 text-gray-700 sm:px-4">{{ $this->residentDisplayFirst($apt) }}</td>
                                        <td class="px-3 py-3 text-gray-700 sm:px-4">{{ $this->residentDisplayLast($apt) }}</td>
                                        <td class="px-3 py-3 text-gray-700 sm:px-4">{{ $apt->ru_phone ?: '—' }}</td>
                                        <td class="max-w-[10rem] truncate px-3 py-3 text-gray-700 sm:px-4" title="{{ $apt->ru_email }}">{{ $apt->ru_email ?: '—' }}</td>
                                        <td class="px-3 py-3 sm:px-4">
                                            @if ($apt->period_meter_reading_id)
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Сданы') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ __('Долг') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            @if ($apt->resident_user_id)
                                                <div class="flex max-w-[11rem] flex-col gap-1">
                                                    @if ($apt->ru_invitation_sent_at)
                                                        <span class="text-xs text-gray-500">{{ __('Отпр. :date', ['date' => $this->formatInvitationDate($apt->ru_invitation_sent_at)]) }}</span>
                                                    @else
                                                        <span class="text-xs text-amber-700">{{ __('Ссылка не отправлялась') }}</span>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        wire:click="sendInvitation({{ (int) $apt->resident_user_id }})"
                                                        wire:loading.attr="disabled"
                                                        class="text-left text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        {{ __('Отправить ссылку на пароль') }}
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            @if ($apt->resident_user_id)
                                                @if ($apt->ru_last_login_at)
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Да') }}</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ __('Нет') }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            @if ($apt->resident_user_id)
                                                @if ($apt->ru_access_suspended_at)
                                                    <button type="button" wire:click="toggleAccess({{ (int) $apt->resident_user_id }})" class="text-xs font-medium text-green-700 hover:text-green-900">{{ __('Открыть доступ') }}</button>
                                                @else
                                                    <button type="button" wire:click="toggleAccess({{ (int) $apt->resident_user_id }})" class="text-xs font-medium text-red-700 hover:text-red-900">{{ __('Закрыть доступ') }}</button>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-500">{{ __('Создайте жильца в «Дома и доступ»') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">{{ __('Нет квартир или ничего не найдено.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-4 sm:px-6">
                        {{ $this->rows->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
