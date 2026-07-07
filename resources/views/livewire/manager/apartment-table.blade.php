<div wire:poll.4s="pollSubmissionUpdates" class="manager-mobile-pad py-6 sm:py-10">
    <x-manager.submission-toast />

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('manager.dashboard') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← {{ __('Обзор') }}</a>
                <h1 class="mt-1 text-2xl font-bold text-indigo-950 sm:text-3xl">{{ __('Жильцы') }}</h1>
            </div>
            <a href="{{ route('manager.readings', ['filter' => $statusFilter === 'submitted' ? 'submitted' : 'debt']) }}" wire:navigate class="inline-flex min-h-[44px] items-center justify-center rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-sky-200 hover:bg-sky-700">
                {{ __('Ввести показания') }}
            </a>
        </div>

        @if (session('apt_ok'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 ring-1 ring-emerald-100">{{ session('apt_ok') }}</div>
        @endif
        @if (session('apt_err'))
            <div class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900 ring-1 ring-rose-100">{{ session('apt_err') }}</div>
        @endif

        <x-manager.context-bar
            :buildings="$this->buildings"
            :building-id="$building_id"
            :period-title="__('Период для статуса показаний')"
            :locked-period-label="$this->managerLockedPeriodLabel"
        />

        @if ($this->buildings->isEmpty())
            <div class="app-card p-6 text-slate-600">{{ __('Нет домов. Создайте в разделе настройки.') }}</div>
        @elseif (! $building_id)
            <div class="app-card p-6 text-slate-600">{{ __('Выберите дом.') }}</div>
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
                        $cardTone = $isDebt
                            ? 'border-l-4 border-l-rose-500 bg-gradient-to-r from-rose-50/80 to-white ring-rose-100'
                            : 'border-l-4 border-l-emerald-500 bg-gradient-to-r from-emerald-50/60 to-white ring-emerald-100';
                    @endphp
                    <div class="app-card overflow-hidden p-4 ring-1 {{ $cardTone }} sm:p-5" wire:key="apt-card-{{ $apt->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-lg font-bold text-indigo-950">{{ __('Кв. :number', ['number' => $apt->number]) }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $this->residentDisplayLast($apt) }} {{ $this->residentDisplayFirst($apt) }}
                                </p>
                            </div>
                            @if ($isDebt)
                                <span class="inline-flex items-center rounded-full bg-rose-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">{{ __('Долг') }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">{{ __('Сданы') }}</span>
                            @endif
                        </div>

                        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                            <div><span class="text-slate-500">{{ __('Телефон') }}:</span> <span class="font-medium">{{ $apt->ru_phone ?: '—' }}</span></div>
                            <div class="break-all sm:col-span-2"><span class="text-slate-500">{{ __('Почта') }}:</span> <span class="font-medium">{{ $apt->ru_email ?: '—' }}</span></div>
                            @if ($apt->resident_user_id)
                                <div>
                                    @if ($apt->ru_last_login_at)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('Входил') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">{{ __('Не входил') }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">
                                    @if ($apt->ru_invitation_sent_at)
                                        {{ __('Отпр. :date', ['date' => $this->formatInvitationDate($apt->ru_invitation_sent_at)]) }}
                                    @else
                                        <span class="text-amber-700">{{ __('Ссылка не отправлялась') }}</span>
                                    @endif
                                </div>
                            @endif
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($apt->resident_user_id)
                                @if ($isDebt)
                                    <a href="{{ route('manager.readings', ['filter' => 'debt']) }}" wire:navigate class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-700">{{ __('Ввести показания') }}</a>
                                @endif
                                <button type="button" wire:click="startEditResident({{ (int) $apt->resident_user_id }})" class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ __('Редактировать') }}</button>
                                <button type="button" wire:click="sendInvitation({{ (int) $apt->resident_user_id }})" wire:loading.attr="disabled" class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-200 hover:bg-indigo-100">{{ __('Ссылка на пароль') }}</button>
                                        @if ($apt->ru_access_suspended_at)
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="(int) $apt->resident_user_id"
                                                :title="__('Открыть доступ жильцу?')"
                                                :confirm-text="__('Открыть доступ')"
                                                icon="question"
                                                confirm-color="#059669"
                                                class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200"
                                            >{{ __('Открыть доступ') }}</x-manager.confirm-button>
                                        @else
                                            <x-manager.confirm-button
                                                wire-method="toggleAccess"
                                                :wire-param="(int) $apt->resident_user_id"
                                                :title="__('Закрыть доступ жильцу?')"
                                                :confirm-text="__('Закрыть доступ')"
                                                icon="warning"
                                                confirm-color="#d97706"
                                                class="inline-flex min-h-[40px] items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800 ring-1 ring-rose-200"
                                            >{{ __('Закрыть доступ') }}</x-manager.confirm-button>
                                        @endif
                            @else
                                <a href="{{ route('manager.setup') }}" wire:navigate class="text-xs font-semibold text-violet-700 hover:text-violet-900">{{ __('Создайте жильца в настройках') }} →</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="app-card p-8 text-center text-slate-500">{{ __('Нет квартир или ничего не найдено.') }}</div>
                @endforelse
            </div>

            <div class="pt-2">
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>

    <x-modal name="edit-resident" :show="$editingResidentId !== null" focusable>
        <form wire:submit="saveResident" class="p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Редактирование жильца') }}</h2>
                @if ($edit_apartment_number !== '')
                    <p class="mt-1 text-sm text-gray-600">{{ __('Кв. :number', ['number' => $edit_apartment_number]) }}</p>
                @endif
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

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="cancelEditResident">{{ __('Отмена') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Сохранить') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
