<div @if ($inBuilding) wire:poll.visible.4s="pollSubmissionUpdates" @endif class="manager-mobile-pad py-6 sm:py-8">
    @if ($inBuilding)
        <x-manager.submission-toast />
    @endif

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if (session('mgr_ok'))
            <div class="k16-alert-success">{{ session('mgr_ok') }}</div>
        @endif
        @if (session('mgr_warn'))
            <div class="k16-alert-warning">{{ session('mgr_warn') }}</div>
        @endif
        @if (session('mgr_err'))
            <div class="k16-alert-danger">{{ session('mgr_err') }}</div>
        @endif

        @if (! $inBuilding || ! $this->selectedBuilding)
            <div>
                <h1 class="k16-page-title">{{ __('Дома') }}</h1>
                <p class="mt-2 k16-page-subtitle">{{ __('Выберите дом, чтобы настроить квартиры и жильцов.') }}</p>
            </div>

            <div class="space-y-3">
                @forelse ($this->buildings as $b)
                    <div wire:key="building-row-{{ $b->id }}" class="k16-card p-5">
                        <p class="text-k16-lead font-bold text-k16-text">{{ $b->name }}</p>
                        <p class="mt-1 text-k16-body text-k16-text-muted">
                            {{ $b->address ?: __('Адрес не указан') }}
                            · {{ __(':count кв.', ['count' => $b->apartments_count]) }}
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="openBuilding({{ $b->id }})" class="k16-btn-primary">
                                {{ __('Открыть') }}
                            </button>
                            <x-k16.action-menu>
                                <x-k16.menu-item action="startEditBuilding" :action-param="$b->id">
                                    {{ __('Изменить') }}
                                </x-k16.menu-item>
                            </x-k16.action-menu>
                        </div>
                    </div>
                @empty
                    <div class="k16-card p-6 text-k16-text-muted">{{ __('Пока нет домов — добавьте первый ниже.') }}</div>
                @endforelse
            </div>

            <form wire:submit="createBuilding" class="k16-card border-dashed p-5 space-y-4">
                <p class="text-k16-lead font-bold text-k16-text">{{ __('Новый дом') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="nbn" :value="__('Название')" />
                        <x-text-input wire:model="new_building_name" id="nbn" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('new_building_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="nba" :value="__('Адрес (необязательно)')" />
                        <x-text-input wire:model="new_building_address" id="nba" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('new_building_address')" class="mt-1" />
                    </div>
                </div>
                <button type="submit" class="k16-btn-primary" wire:loading.attr="disabled" wire:target="createBuilding">
                    <span wire:loading.remove wire:target="createBuilding">{{ __('Добавить дом') }}</span>
                    <span wire:loading wire:target="createBuilding">Lūdzu uzgaidiet</span>
                </button>
            </form>
        @else
            @php $building = $this->selectedBuilding; @endphp
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <button type="button" wire:click="backToBuildings" class="text-base font-semibold text-k16-accent">
                        ← {{ __('Все дома') }}
                    </button>
                    <h1 class="mt-2 k16-page-title">{{ $building->name }}</h1>
                    <p class="mt-1 k16-page-subtitle">
                        {{ $building->address ?: __('Адрес не указан') }}
                        · {{ __(':count кв.', ['count' => $building->apartments_count]) }}
                    </p>
                </div>
                <a href="{{ route('manager.readings', ['filter' => $statusFilter === 'submitted' ? 'submitted' : 'debt']) }}" wire:navigate class="k16-btn-primary shrink-0">
                    {{ __('Ввести показания') }}
                </a>
            </div>

            <x-manager.context-bar
                :buildings="$this->buildings"
                :building-id="$building_id"
                year-model="statusYear"
                month-model="statusMonth"
                :period-title="__('Период для статуса показаний')"
                :locked-period-label="$this->managerLockedPeriodLabel"
            />

            <div class="space-y-4">
                <x-manager.status-filter :active="$statusFilter" />

                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-md flex-1">
                        <x-input-label for="apt-search" :value="__('Поиск')" />
                        <x-text-input
                            wire:model.live.debounce.300ms="search"
                            id="apt-search"
                            type="search"
                            class="mt-1 block w-full"
                            :placeholder="__('Квартира, ФИО, email, телефон…')"
                        />
                    </div>
                    <button type="button" wire:click="startCreateApartment" class="k16-btn-primary shrink-0">
                        {{ __('Добавить квартиру') }}
                    </button>
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
                                'k16-filter-chip',
                                'bg-k16-accent text-white' => $sortField === $field,
                                'border border-k16-border bg-k16-surface text-k16-text' => $sortField !== $field,
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

            <div class="space-y-3">
                @forelse ($this->apartments as $apt)
                    @php
                        $isDebt = ! $apt->period_meter_reading_id;
                        $isManagerOccupant = $apt->ru_role === \App\Enums\UserRole::Manager->value;
                    @endphp
                    <div wire:key="apt-card-{{ $apt->id }}" class="k16-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-k16-lead font-bold text-k16-text">{{ __('Кв. :number', ['number' => $apt->number]) }}</p>
                                @if ($apt->area_m2)
                                    <p class="mt-1 text-k16-body text-k16-text-muted">{{ __(':area м²', ['area' => $apt->area_m2]) }}</p>
                                @endif
                                @if ($apt->occupant_user_id)
                                    <p class="mt-1 text-k16-body text-k16-text-muted">
                                        {{ $this->occupantDisplayLast($apt) }} {{ $this->occupantDisplayFirst($apt) }}
                                    </p>
                                @endif
                            </div>
                            @if ($apt->occupant_user_id)
                                @if ($isDebt)
                                    <span class="k16-badge-danger">{{ __('Не сдано') }}</span>
                                @else
                                    <span class="k16-badge-success">{{ __('Сдано') }}</span>
                                @endif
                            @endif
                        </div>

                        @if ($apt->occupant_user_id)
                            <dl class="mt-4 grid gap-2 text-k16-body sm:grid-cols-2">
                                <div>
                                    <span class="text-k16-text-muted">{{ __('Телефон') }}:</span>
                                    <span class="font-medium">{{ $apt->ru_phone ?: '—' }}</span>
                                </div>
                                <div class="break-all sm:col-span-2">
                                    <span class="text-k16-text-muted">{{ __('Почта') }}:</span>
                                    <span class="font-medium">{{ $apt->ru_email ?: '—' }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($isManagerOccupant)
                                        <span class="k16-badge-warning">{{ __('Управляющий') }}</span>
                                    @endif
                                    @if ($apt->ru_last_login_at)
                                        <span class="k16-badge-success">{{ __('Входил') }}</span>
                                    @else
                                        <span class="k16-badge-warning">{{ __('Не входил') }}</span>
                                    @endif
                                    @if ($apt->ru_access_suspended_at)
                                        <span class="k16-badge-danger">{{ __('Доступ закрыт') }}</span>
                                    @endif
                                </div>
                                <div class="text-base text-k16-text-muted">
                                    @if ($apt->ru_invitation_sent_at)
                                        {{ __('Отпр. :date', ['date' => $this->formatInvitationDate($apt->ru_invitation_sent_at)]) }}
                                    @else
                                        <span class="text-k16-warning">{{ __('Ссылка не отправлялась') }}</span>
                                    @endif
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                @if ($isDebt)
                                    <a href="{{ route('manager.readings.apartment', ['apartment' => $apt->id]) }}" wire:navigate class="k16-btn-primary">
                                        {{ __('Ввести показания') }}
                                    </a>
                                @else
                                    <a href="{{ route('manager.readings.apartment', ['apartment' => $apt->id]) }}" wire:navigate class="k16-btn-primary">
                                        {{ __('История показаний') }}
                                    </a>
                                @endif

                                <x-k16.action-menu>
                                    <x-k16.menu-item action="startEditResident" :action-param="(int) $apt->occupant_user_id">
                                        {{ __('Редактировать') }}
                                    </x-k16.menu-item>
                                    @if (! $isManagerOccupant)
                                        <x-k16.menu-item action="sendInvitation" :action-param="(int) $apt->occupant_user_id">
                                            {{ __('Ссылка на пароль') }}
                                        </x-k16.menu-item>
                                    @endif
                                    @if ($apt->ru_access_suspended_at)
                                        <li class="list-none">
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="(int) $apt->occupant_user_id"
                                                :title="__('Открыть доступ жильцу?')"
                                                :confirm-text="__('Открыть доступ')"
                                                tone="success"
                                                class="block w-full px-4 py-3 text-start text-base font-semibold text-k16-success hover:bg-k16-success-soft"
                                            >{{ __('Открыть доступ') }}</x-manager.confirm-button>
                                        </li>
                                    @else
                                        <li class="list-none">
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="(int) $apt->occupant_user_id"
                                                :title="__('Закрыть доступ жильцу?')"
                                                :confirm-text="__('Закрыть доступ')"
                                                tone="danger"
                                                class="block w-full px-4 py-3 text-start text-base font-semibold text-k16-danger hover:bg-k16-danger-soft"
                                            >{{ __('Закрыть доступ') }}</x-manager.confirm-button>
                                        </li>
                                    @endif
                                </x-k16.action-menu>
                            </div>
                        @else
                            <p class="mt-3 text-k16-body text-k16-text-muted">{{ __('Жилец не назначен') }}</p>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="startCreateResident({{ $apt->id }})" class="k16-btn-primary">
                                    {{ __('Добавить жильца') }}
                                </button>
                                <x-k16.action-menu>
                                    <x-k16.menu-item action="startEditApartment" :action-param="$apt->id">
                                        {{ __('Редактировать') }}
                                    </x-k16.menu-item>
                                    <x-k16.menu-item :href="route('manager.readings.apartment', ['apartment' => $apt->id])">
                                        {{ __('Показания') }}
                                    </x-k16.menu-item>
                                </x-k16.action-menu>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="k16-card p-8 text-center text-k16-text-muted">
                        {{ $search !== '' || $statusFilter !== 'all' ? __('Нет квартир или ничего не найдено.') : __('В этом доме пока нет квартир.') }}
                    </div>
                @endforelse

                @if ($this->apartments->hasPages())
                    <div class="pt-2">{{ $this->apartments->links() }}</div>
                @endif
            </div>
        @endif
    </div>

    <x-modal name="edit-building" variant="k16" :show="$editingBuildingId !== null" focusable>
        <form wire:submit="saveBuilding" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Редактирование дома') }}</h2>
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
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEditBuilding" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="create-apartment" variant="k16" :show="$creatingApartment" focusable>
        <form wire:submit="createApartment" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Новая квартира') }}</h2>
            <p class="text-k16-body text-k16-text-muted">{{ __('Пароль жилец задаёт сам по ссылке из письма.') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="nan" :value="__('Номер квартиры')" />
                    <x-text-input wire:model="new_apartment_number" id="nan" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('new_apartment_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="naa" :value="__('Площадь, м²')" />
                    <x-text-input wire:model="new_apartment_area_m2" id="naa" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" placeholder="45.84" />
                    <x-input-error :messages="$errors->get('new_apartment_area_m2')" class="mt-1" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="can-rfn" :value="__('Имя')" />
                    <x-text-input wire:model="resident_first_name" id="can-rfn" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('resident_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="can-rln" :value="__('Фамилия')" />
                    <x-text-input wire:model="resident_last_name" id="can-rln" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('resident_last_name')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="can-rph" :value="__('Телефон (необязательно)')" />
                <x-text-input wire:model="resident_phone" id="can-rph" type="tel" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('resident_phone')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="can-re" :value="__('Email (логин)')" />
                <x-text-input wire:model="resident_email" id="can-re" type="email" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('resident_email')" class="mt-1" />
            </div>
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelCreateApartment" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Добавить') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="edit-apartment" variant="k16" :show="$editingApartmentId !== null" focusable>
        <form wire:submit="saveApartment" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Редактирование квартиры') }}</h2>
            <div>
                <x-input-label for="ean" :value="__('Номер квартиры')" />
                <x-text-input wire:model="edit_apartment_number" id="ean" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('edit_apartment_number')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="eaa" :value="__('Площадь, м²')" />
                <x-text-input wire:model="edit_apartment_area_m2" id="eaa" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" placeholder="45.84" />
                <x-input-error :messages="$errors->get('edit_apartment_area_m2')" class="mt-1" />
            </div>
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEditApartment" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="create-resident" variant="k16" :show="$creatingResidentForApartmentId !== null" focusable>
        <form wire:submit="createResident" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Новый жилец') }}</h2>
            <p class="text-sm text-k16-text-muted">{{ __('Если указать email существующего управляющего, он будет привязан к квартире — тот же пароль для кабинета жильца.') }}</p>
            <p class="text-k16-body text-k16-text-muted">{{ __('Пароль жилец задаёт сам по ссылке из письма.') }}</p>
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
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelCreateResident" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Создать жильца') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="edit-resident" variant="k16" :show="$editingResidentId !== null" focusable>
        <form wire:submit="saveResident" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Редактирование') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="eran" :value="__('Номер квартиры')" />
                    <x-text-input wire:model="edit_apartment_number" id="eran" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_apartment_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="eraa" :value="__('Площадь, м²')" />
                    <x-text-input wire:model="edit_apartment_area_m2" id="eraa" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" placeholder="45.84" />
                    <x-input-error :messages="$errors->get('edit_apartment_area_m2')" class="mt-1" />
                </div>
            </div>
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
            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEditResident" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>
</div>
