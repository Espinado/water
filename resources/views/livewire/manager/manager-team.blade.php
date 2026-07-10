<div class="manager-mobile-pad py-6 sm:py-8">
    <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h1 class="k16-page-title">{{ __('Команда') }}</h1>
            <p class="mt-2 k16-page-subtitle">{{ __('Управляющие с доступом к панели дома. Если управляющий живёт в доме — привяжите его квартиру: тот же email и пароль подойдут для приложения жильца.') }}</p>
        </div>

        @if (session('mgr_ok'))
            <div class="k16-alert-success">{{ session('mgr_ok') }}</div>
        @endif
        @if (session('mgr_warn'))
            <div class="k16-alert-warning">{{ session('mgr_warn') }}</div>
        @endif
        @if (session('mgr_err'))
            <div class="k16-alert-danger">{{ session('mgr_err') }}</div>
        @endif

        <div class="space-y-3">
            @forelse ($this->managers as $manager)
                <div wire:key="manager-{{ $manager->id }}" class="k16-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-k16-lead font-bold text-k16-text">
                                {{ $this->displayName($manager) }}
                                @if ($manager->id === auth()->id())
                                    <span class="text-k16-body font-normal text-k16-text-muted">({{ __('вы') }})</span>
                                @endif
                            </p>
                            <p class="mt-1 text-k16-body text-k16-text-muted">{{ $manager->email }}</p>
                            <p class="text-k16-body text-k16-text-muted">{{ $manager->phone ?: '—' }}</p>
                        </div>
                        @php $tone = $this->statusTone($manager); @endphp
                        <span @class([
                            'k16-badge-success' => $tone === 'success',
                            'k16-badge-warning' => $tone === 'warning',
                            'k16-badge-danger' => $tone === 'danger',
                        ])>{{ $this->statusLabel($manager) }}</span>
                    </div>

                    <dl class="mt-4 grid gap-2 text-k16-body text-k16-text-muted sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-k16-text">{{ __('Приглашение') }}</dt>
                            <dd class="mt-0.5">{{ $this->formatDate($manager->invitation_sent_at) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-k16-text">{{ __('Последний вход') }}</dt>
                            <dd class="mt-0.5">{{ $this->formatDate($manager->last_login_at) }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-k16-text">{{ __('Квартира (приложение жильца)') }}</dt>
                            <dd class="mt-0.5">{{ $this->apartmentLabel($manager) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="startEdit({{ $manager->id }})" class="k16-btn-primary">
                            {{ __('Изменить') }}
                        </button>
                        <x-k16.action-menu>
                            <x-k16.menu-item action="resendInvitation" :action-param="$manager->id">
                                {{ __('Отправить приглашение') }}
                            </x-k16.menu-item>
                            @if ($manager->id !== auth()->id())
                                @if ($manager->access_suspended_at)
                                    <x-k16.menu-item action="restoreManager" :action-param="$manager->id">
                                        {{ __('Восстановить доступ') }}
                                    </x-k16.menu-item>
                                @else
                                    <li class="list-none">
                                        <x-k16.confirm-button
                                            wire-method="suspendManager"
                                            :wire-param="$manager->id"
                                            :title="__('Отключить доступ управляющему?')"
                                            :text="__('Управляющий не сможет войти в панель, пока доступ не будет восстановлен.')"
                                            :confirm-text="__('Отключить')"
                                            tone="danger"
                                            class="block w-full px-4 py-3 text-start text-base font-semibold text-k16-danger hover:bg-k16-danger-soft"
                                        >{{ __('Отключить доступ') }}</x-k16.confirm-button>
                                    </li>
                                @endif
                                <li class="list-none">
                                    <x-k16.confirm-button
                                        wire-method="deleteManager"
                                        :wire-param="$manager->id"
                                        :title="__('Удалить управляющего?')"
                                        :text="__('Учётная запись :name будет удалена без возможности восстановления.', ['name' => $this->displayName($manager)])"
                                        :confirm-text="__('Удалить')"
                                        tone="danger"
                                        class="block w-full px-4 py-3 text-start text-base font-semibold text-k16-danger hover:bg-k16-danger-soft"
                                    >{{ __('Удалить') }}</x-k16.confirm-button>
                                </li>
                            @endif
                        </x-k16.action-menu>
                    </div>
                </div>
            @empty
                <div class="k16-card p-6 text-k16-text-muted">{{ __('Пока нет других управляющих — добавьте первого ниже.') }}</div>
            @endforelse
        </div>

        <form wire:submit="createManager" class="k16-card border-dashed p-5 space-y-4">
            <p class="text-k16-lead font-bold text-k16-text">{{ __('Новый управляющий') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="new-first-name" :value="__('Имя')" />
                    <x-text-input wire:model="new_first_name" id="new-first-name" class="mt-1 block w-full" autocomplete="given-name" />
                    <x-input-error :messages="$errors->get('new_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="new-last-name" :value="__('Фамилия')" />
                    <x-text-input wire:model="new_last_name" id="new-last-name" class="mt-1 block w-full" autocomplete="family-name" />
                    <x-input-error :messages="$errors->get('new_last_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="new-email" :value="__('Email')" />
                    <x-text-input wire:model="new_email" id="new-email" type="email" class="mt-1 block w-full" autocomplete="email" />
                    <x-input-error :messages="$errors->get('new_email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="new-phone" :value="__('Телефон')" />
                    <x-text-input wire:model="new_phone" id="new-phone" type="tel" class="mt-1 block w-full" autocomplete="tel" />
                    <x-input-error :messages="$errors->get('new_phone')" class="mt-1" />
                </div>
            </div>
            <p class="text-k16-body text-k16-text-muted">{{ __('После добавления на email придёт ссылка для установки пароля.') }}</p>
            <button type="submit" class="k16-btn-primary">{{ __('Добавить и отправить приглашение') }}</button>
        </form>
    </div>

    <x-modal name="edit-manager" variant="k16" :show="$editingId !== null" focusable>
        <form wire:submit="saveManager" class="k16-modal-panel space-y-4">
            <h2 class="k16-modal-title">{{ __('Редактирование управляющего') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit-first-name" :value="__('Имя')" />
                    <x-text-input wire:model="edit_first_name" id="edit-first-name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="edit-last-name" :value="__('Фамилия')" />
                    <x-text-input wire:model="edit_last_name" id="edit-last-name" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_last_name')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-email" :value="__('Email')" />
                    <x-text-input wire:model="edit_email" id="edit-email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('edit_email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-apartment" :value="__('Квартира (необязательно)')" />
                    <select wire:model="edit_apartment_id" id="edit-apartment" class="mt-1 block w-full rounded-xl border-k16-border shadow-sm focus:border-k16-accent focus:ring-k16-accent">
                        <option value="">{{ __('Не привязан к квартире') }}</option>
                        @foreach ($this->apartments as $apartment)
                            <option value="{{ $apartment->id }}">{{ $apartment->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-k16-text-muted">{{ __('Один email — для входа и в панель управляющего, и в кабинет жильца этой квартиры.') }}</p>
                    <x-input-error :messages="$errors->get('edit_apartment_id')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit-phone" :value="__('Телефон')" />
                    <x-text-input wire:model="edit_phone" id="edit-phone" type="tel" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('edit_phone')" class="mt-1" />
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEdit" class="k16-btn-secondary">{{ __('Отмена') }}</button>
                <button type="submit" class="k16-btn-primary">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </x-modal>
</div>
