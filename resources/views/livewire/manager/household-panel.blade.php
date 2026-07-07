<div class="manager-mobile-pad py-6 sm:py-10">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
        @if (session('mgr_ok'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 ring-1 ring-emerald-100">{{ session('mgr_ok') }}</div>
        @endif
        @if (session('mgr_warn'))
            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 ring-1 ring-amber-100">{{ session('mgr_warn') }}</div>
        @endif
        @if (session('mgr_err'))
            <div class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900 ring-1 ring-rose-100">{{ session('mgr_err') }}</div>
        @endif

        @if (! $inBuilding || ! $this->selectedBuilding)
            {{-- ===== Список домов ===== --}}
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-violet-600">{{ __('Панель управляющего') }}</p>
                <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ __('Управление домами') }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ __('Выберите дом, чтобы увидеть квартиры и жильцов.') }}</p>
            </div>

            <section class="app-card overflow-hidden">
                <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50 to-indigo-50 px-4 py-4 sm:px-6">
                    <h2 class="text-lg font-bold text-indigo-950">{{ __('Дома') }}</h2>
                </div>

                <div class="space-y-3 p-4 sm:p-6">
                    @forelse ($this->buildings as $b)
                        <div wire:key="building-row-{{ $b->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-bold text-indigo-950">{{ $b->name }}</p>
                                    <p class="mt-0.5 text-sm text-slate-600">
                                        {{ $b->address ?: __('Адрес не указан') }}
                                        · {{ __(':count кв.', ['count' => $b->apartments_count]) }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="startEditBuilding({{ $b->id }})" class="inline-flex min-h-[40px] items-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                                        {{ __('Изменить') }}
                                    </button>
                                    <button type="button" wire:click="openBuilding({{ $b->id }})" class="inline-flex min-h-[40px] items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                                        {{ __('Открыть') }} →
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Пока нет домов — добавьте первый ниже.') }}</p>
                    @endforelse

                    <form wire:submit="createBuilding" class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/40 p-4 space-y-3">
                        <p class="text-sm font-semibold text-violet-900">{{ __('Новый дом') }}</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label for="nbn" :value="__('Название')" />
                                <x-text-input wire:model="new_building_name" id="nbn" class="mt-1 block w-full rounded-xl" />
                                <x-input-error :messages="$errors->get('new_building_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="nba" :value="__('Адрес (необязательно)')" />
                                <x-text-input wire:model="new_building_address" id="nba" class="mt-1 block w-full rounded-xl" />
                                <x-input-error :messages="$errors->get('new_building_address')" class="mt-1" />
                            </div>
                        </div>
                        <x-primary-button type="submit">{{ __('Добавить дом') }}</x-primary-button>
                    </form>
                </div>
            </section>
        @else
            {{-- ===== Внутри дома: квартиры и жильцы ===== --}}
            @php $building = $this->selectedBuilding; @endphp
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <button type="button" wire:click="backToBuildings" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← {{ __('Все дома') }}</button>
                    <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ $building->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $building->address ?: __('Адрес не указан') }}
                        · {{ __(':count кв.', ['count' => $building->apartments_count]) }}
                    </p>
                </div>
                <button type="button" wire:click="startEditBuilding({{ $building->id }})" class="inline-flex min-h-[44px] items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                    {{ __('Изменить дом') }}
                </button>
            </div>

            <section class="app-card overflow-hidden">
                <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 to-indigo-50 px-4 py-4 sm:px-6">
                    <h2 class="text-lg font-bold text-indigo-950">{{ __('Квартиры и жильцы') }}</h2>
                </div>

                <div class="space-y-4 border-b border-slate-100 p-4 sm:p-6">
                    <div class="max-w-md">
                        <x-input-label for="apt-search" :value="__('Поиск')" />
                        <x-text-input
                            wire:model.live.debounce.300ms="search"
                            id="apt-search"
                            type="search"
                            class="mt-1 block w-full rounded-xl"
                            :placeholder="__('Квартира, ФИО, email, телефон…')"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Сортировка') }}">
                        @foreach ([
                            'number' => __('Кв.'),
                            'last_name' => __('Фамилия'),
                            'first_name' => __('Имя'),
                            'phone' => __('Телефон'),
                            'email' => __('Почта'),
                        ] as $field => $label)
                            <button
                                type="button"
                                wire:click="sortBy('{{ $field }}')"
                                @class([
                                    'rounded-full px-3 py-1.5 text-xs font-semibold transition min-h-[36px]',
                                    'bg-indigo-600 text-white shadow-sm' => $sortField === $field,
                                    'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200 hover:bg-indigo-100' => $sortField !== $field,
                                ])
                            >
                                {{ $label }}
                                @if ($sortField === $field)
                                    {{ $sortAsc ? '↑' : '↓' }}
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3 p-4 sm:p-6">
                    @forelse ($this->apartments as $apt)
                        @php $resident = $apt->users->first(); @endphp
                        <div wire:key="apt-card-{{ $apt->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100 sm:p-5">
                            <p class="text-lg font-bold text-indigo-950">{{ __('Кв. :number', ['number' => $apt->number]) }}</p>

                            <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Жилец') }}</p>
                                @if ($resident)
                                    <div class="mt-2 space-y-1 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $resident->last_name }} {{ $resident->first_name }}</p>
                                        <p class="text-slate-600">{{ $resident->email }}</p>
                                        <p class="text-slate-600">{{ $resident->phone ?: '—' }}</p>
                                        <div class="flex flex-wrap gap-2 pt-2">
                                            @if ($resident->last_login_at)
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Входил') }}</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">{{ __('Не входил') }}</span>
                                            @endif
                                            @if ($resident->access_suspended_at)
                                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">{{ __('Доступ закрыт') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button" wire:click="startEditResident({{ $resident->id }})" class="inline-flex min-h-[40px] items-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-700">{{ __('Редактировать') }}</button>
                                        <a href="{{ route('manager.readings.apartment', ['apartment' => $apt->id]) }}" wire:navigate class="inline-flex min-h-[40px] items-center rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-700">{{ __('Показания') }}</a>
                                        <button type="button" wire:click="sendInvitation({{ $resident->id }})" wire:loading.attr="disabled" class="inline-flex min-h-[40px] items-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-200">{{ __('Ссылка на пароль') }}</button>
                                        @if ($resident->access_suspended_at)
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="$resident->id"
                                                :title="__('Открыть доступ жильцу?')"
                                                :confirm-text="__('Открыть доступ')"
                                                icon="question"
                                                confirm-color="#059669"
                                                class="inline-flex min-h-[40px] items-center rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200"
                                            >{{ __('Открыть доступ') }}</x-manager.confirm-button>
                                        @else
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="$resident->id"
                                                :title="__('Закрыть доступ жильцу?')"
                                                :confirm-text="__('Закрыть доступ')"
                                                icon="warning"
                                                confirm-color="#d97706"
                                                class="inline-flex min-h-[40px] items-center rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-200"
                                            >{{ __('Закрыть доступ') }}</x-manager.confirm-button>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-slate-500">{{ __('Жилец не назначен') }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button" wire:click="startCreateResident({{ $apt->id }})" class="inline-flex min-h-[40px] items-center rounded-xl bg-violet-600 px-4 py-2 text-xs font-bold text-white hover:bg-violet-700">
                                            + {{ __('Добавить жильца') }}
                                        </button>
                                        <a href="{{ route('manager.readings.apartment', ['apartment' => $apt->id]) }}" wire:navigate class="inline-flex min-h-[40px] items-center rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-700">{{ __('Показания') }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            @if ($search !== '')
                                {{ __('Ничего не найдено.') }}
                            @else
                                {{ __('В этом доме пока нет квартир.') }}
                            @endif
                        </p>
                    @endforelse

                    @if ($this->apartments->hasPages())
                        <div class="pt-2">
                            {{ $this->apartments->links() }}
                        </div>
                    @endif

                    <form wire:submit="createApartment" class="rounded-2xl border border-dashed border-sky-200 bg-sky-50/40 p-4">
                        <p class="text-sm font-semibold text-sky-900">{{ __('Новая квартира') }}</p>
                        <div class="mt-3 flex flex-wrap gap-3 items-end">
                            <div class="min-w-[8rem] flex-1">
                                <x-input-label for="nan" :value="__('Номер')" />
                                <x-text-input wire:model="new_apartment_number" id="nan" class="mt-1 block w-full rounded-xl" />
                                <x-input-error :messages="$errors->get('new_apartment_number')" class="mt-1" />
                            </div>
                            <x-primary-button type="submit">{{ __('Добавить') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </section>
        @endif
    </div>

    {{-- Модал: редактирование дома --}}
    <x-modal name="edit-building" :show="$editingBuildingId !== null" focusable>
        <form wire:submit="saveBuilding" class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Редактирование дома') }}</h2>
            <div>
                <x-input-label for="ebn" :value="__('Название')" />
                <x-text-input wire:model="edit_building_name" id="ebn" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('edit_building_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="eba" :value="__('Адрес (необязательно)')" />
                <x-text-input wire:model="edit_building_address" id="eba" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('edit_building_address')" class="mt-1" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="cancelEditBuilding">{{ __('Отмена') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Сохранить') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Модал: редактирование квартиры --}}
    <x-modal name="edit-apartment" :show="$editingApartmentId !== null" focusable>
        <form wire:submit="saveApartment" class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Редактирование квартиры') }}</h2>
            <div>
                <x-input-label for="ean" :value="__('Номер квартиры')" />
                <x-text-input wire:model="edit_apartment_number" id="ean" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('edit_apartment_number')" class="mt-1" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="cancelEditApartment">{{ __('Отмена') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Сохранить') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Модал: новый жилец --}}
    <x-modal name="create-resident" :show="$creatingResidentForApartmentId !== null" focusable>
        <form wire:submit="createResident" class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Новый жилец') }}</h2>
            <p class="text-sm text-slate-600">{{ __('Пароль жилец задаёт сам по ссылке из письма.') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="rfn" :value="__('Имя')" />
                    <x-text-input wire:model="resident_first_name" id="rfn" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('resident_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="rln" :value="__('Фамилия')" />
                    <x-text-input wire:model="resident_last_name" id="rln" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('resident_last_name')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="rph" :value="__('Телефон (необязательно)')" />
                <x-text-input wire:model="resident_phone" id="rph" type="tel" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('resident_phone')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="re" :value="__('Email (логин)')" />
                <x-text-input wire:model="resident_email" id="re" type="email" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('resident_email')" class="mt-1" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="cancelCreateResident">{{ __('Отмена') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Создать жильца') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Модал: редактирование жильца --}}
    <x-modal name="edit-resident" :show="$editingResidentId !== null" focusable>
        <form wire:submit="saveResident" class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Редактирование жильца') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="erfn" :value="__('Имя')" />
                    <x-text-input wire:model="edit_resident_first_name" id="erfn" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_resident_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="erln" :value="__('Фамилия')" />
                    <x-text-input wire:model="edit_resident_last_name" id="erln" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_resident_last_name')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="erph" :value="__('Телефон (необязательно)')" />
                <x-text-input wire:model="edit_resident_phone" id="erph" type="tel" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('edit_resident_phone')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="ere" :value="__('Email (логин)')" />
                <x-text-input wire:model="edit_resident_email" id="ere" type="email" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('edit_resident_email')" class="mt-1" />
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="cancelEditResident">{{ __('Отмена') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Сохранить') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
