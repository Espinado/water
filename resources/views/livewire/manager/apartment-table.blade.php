<div wire:poll.4s="pollSubmissionUpdates" class="manager-mobile-pad py-6 sm:py-8">
    <x-manager.submission-toast />

    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="k16-page-title">{{ __('Жильцы') }}</h1>
            </div>
            <a href="{{ route('manager.readings', ['filter' => $statusFilter === 'submitted' ? 'submitted' : 'debt']) }}" wire:navigate class="k16-btn-primary">
                {{ __('Ввести показания') }}
            </a>
        </div>

        @if (session('apt_ok'))
            <div class="k16-alert-success">{{ session('apt_ok') }}</div>
        @endif
        @if (session('apt_err'))
            <div class="k16-alert-danger">{{ session('apt_err') }}</div>
        @endif

        <x-manager.context-bar
            :buildings="$this->buildings"
            :building-id="$building_id"
            :period-title="__('Период для статуса показаний')"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->buildings->isEmpty())
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Нет домов. Создайте в разделе «Дома».') }}</div>
        @elseif (! $building_id)
            <div class="k16-card p-6 text-k16-text-muted">{{ __('Выберите дом.') }}</div>
        @else
            <div class="space-y-4">
                <x-manager.status-filter :active="$statusFilter" />

                <div class="max-w-md">
                    <x-input-label for="search" :value="__('Поиск')" />
                    <x-text-input wire:model.live.debounce.300ms="search" id="search" type="search" class="mt-1 block w-full rounded-xl" :placeholder="__('Квартира, ФИО, email, телефон…')" />
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->rows as $apt)
                    @php
                        $isDebt = ! $apt->period_meter_reading_id;
                    @endphp
                    <div class="k16-card p-5" wire:key="apt-card-{{ $apt->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-k16-lead font-bold text-k16-text">{{ __('Кв. :number', ['number' => $apt->number]) }}</p>
                                <p class="mt-1 text-k16-body text-k16-text-muted">
                                    {{ $this->residentDisplayLast($apt) }} {{ $this->residentDisplayFirst($apt) }}
                                </p>
                            </div>
                            @if ($isDebt)
                                <span class="k16-badge-danger">{{ __('Не сдано') }}</span>
                            @else
                                <span class="k16-badge-success">{{ __('Сдано') }}</span>
                            @endif
                        </div>

                        <dl class="mt-4 grid gap-2 text-k16-body sm:grid-cols-2">
                            <div><span class="text-k16-text-muted">{{ __('Телефон') }}:</span> <span class="font-medium">{{ $apt->ru_phone ?: '—' }}</span></div>
                            <div class="break-all sm:col-span-2"><span class="text-k16-text-muted">{{ __('Почта') }}:</span> <span class="font-medium">{{ $apt->ru_email ?: '—' }}</span></div>
                            @if ($apt->resident_user_id)
                                <div>
                                    @if ($apt->ru_last_login_at)
                                        <span class="k16-badge-success">{{ __('Входил') }}</span>
                                    @else
                                        <span class="k16-badge-warning">{{ __('Не входил') }}</span>
                                    @endif
                                </div>
                                <div class="text-base text-k16-text-muted">
                                    @if ($apt->ru_invitation_sent_at)
                                        {{ __('Отпр. :date', ['date' => $this->formatInvitationDate($apt->ru_invitation_sent_at)]) }}
                                    @else
                                        <span class="text-k16-warning">{{ __('Ссылка не отправлялась') }}</span>
                                    @endif
                                </div>
                            @endif
                        </dl>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            @if ($apt->resident_user_id)
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
                                    <x-k16.menu-item action="startEditResident" :action-param="(int) $apt->resident_user_id">
                                        {{ __('Редактировать') }}
                                    </x-k16.menu-item>
                                    <x-k16.menu-item action="sendInvitation" :action-param="(int) $apt->resident_user_id">
                                        {{ __('Ссылка на пароль') }}
                                    </x-k16.menu-item>
                                    @if ($apt->ru_access_suspended_at)
                                        <li class="list-none">
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="(int) $apt->resident_user_id"
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
                                                :wire-param="(int) $apt->resident_user_id"
                                                :title="__('Закрыть доступ жильцу?')"
                                                :confirm-text="__('Закрыть доступ')"
                                                tone="danger"
                                                class="block w-full px-4 py-3 text-start text-base font-semibold text-k16-danger hover:bg-k16-danger-soft"
                                            >{{ __('Закрыть доступ') }}</x-manager.confirm-button>
                                        </li>
                                    @endif
                                </x-k16.action-menu>
                            @else
                                <a href="{{ route('manager.setup') }}" wire:navigate class="k16-btn-primary">
                                    {{ __('Добавить жильца') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="k16-card p-8 text-center text-k16-text-muted">{{ __('Нет квартир или ничего не найдено.') }}</div>
                @endforelse
            </div>

            <div class="pt-2">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>

    <x-modal name="edit-resident" variant="k16" :show="$editingResidentId !== null" focusable>
        <form wire:submit="saveResident" class="k16-modal-panel space-y-4">
            <div>
                <h2 class="k16-modal-title">{{ __('Редактирование') }}</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit_apartment_number" :value="__('Номер квартиры')" />
                    <x-text-input wire:model="edit_apartment_number" id="edit_apartment_number" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_apartment_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_apartment_area_m2" :value="__('Площадь, м²')" />
                    <x-text-input wire:model="edit_apartment_area_m2" id="edit_apartment_area_m2" type="text" inputmode="decimal" class="mt-1 block w-full text-right font-semibold" placeholder="45.84" />
                    <x-input-error :messages="$errors->get('edit_apartment_area_m2')" class="mt-1" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit_first_name" :value="__('Имя')" />
                    <x-text-input wire:model="edit_first_name" id="edit_first_name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit_last_name" :value="__('Фамилия')" />
                    <x-text-input wire:model="edit_last_name" id="edit_last_name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_last_name')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="edit_phone" :value="__('Телефон (необязательно)')" />
                <x-text-input wire:model="edit_phone" id="edit_phone" type="tel" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('edit_phone')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="edit_email" :value="__('Email (логин)')" />
                <x-text-input wire:model="edit_email" id="edit_email" type="email" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('edit_email')" class="mt-1" />
            </div>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEditResident" class="k16-btn-secondary w-full sm:w-auto">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary w-full sm:w-auto">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>
</div>
