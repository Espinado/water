<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-4 sm:px-0">
                <h1 class="text-3xl sm:text-4xl font-bold text-indigo-900">Дома и доступ жильцов</h1>
                <a href="{{ route('dashboard') }}" wire:navigate class="text-base font-semibold text-indigo-700 hover:text-indigo-900">← В кабинет</a>
            </div>

            @if (session('mgr_ok'))
                <div class="rounded-xl bg-emerald-50 p-4 text-base text-emerald-900 mx-4 sm:mx-0">{{ session('mgr_ok') }}</div>
            @endif
            @if (session('mgr_warn'))
                <div class="rounded-xl bg-amber-50 p-4 text-base text-amber-900 mx-4 sm:mx-0">{{ session('mgr_warn') }}</div>
            @endif

            <div class="app-card mx-4 sm:mx-0">
                <div class="p-6 space-y-4 border-b border-gray-100">
                    <h2 class="text-2xl font-bold text-indigo-900">Новый дом</h2>
                    <form wire:submit="createBuilding" class="grid gap-4 sm:grid-cols-2 max-w-3xl">
                        <div>
                            <x-input-label for="nbn" value="Название" />
                            <x-text-input wire:model="new_building_name" id="nbn" class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('new_building_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="nba" value="Адрес (необязательно)" />
                            <x-text-input wire:model="new_building_address" id="nba" class="block mt-1 w-full" />
                            <x-input-error :messages="$errors->get('new_building_address')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-primary-button type="submit">Добавить дом</x-primary-button>
                        </div>
                    </form>
                </div>

                @if ($this->buildings->isNotEmpty())
                    <div class="border-b border-gray-200 px-6 pt-4">
                        <nav class="-mb-px flex flex-wrap gap-1 sm:gap-2" aria-label="Дома">
                            @foreach ($this->buildings as $b)
                                <button
                                    type="button"
                                    wire:click="$set('building_id', {{ $b->id }})"
                                    wire:key="tab-building-{{ $b->id }}"
                                    @class([
                                        'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition sm:px-4',
                                        'border-indigo-600 text-indigo-600' => (int) $building_id === (int) $b->id,
                                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => (int) $building_id !== (int) $b->id,
                                    ])
                                >
                                    {{ $b->name }}
                                    <span class="font-normal text-indigo-300">({{ $b->apartments_count }})</span>
                                </button>
                            @endforeach
                        </nav>
                    </div>
                @endif

                <div class="p-6 space-y-4 border-b border-gray-100">
                    <h2 class="text-2xl font-bold text-indigo-900">Квартиры выбранного дома</h2>
                    @if ($this->buildings->isEmpty())
                        <p class="text-sm text-gray-500">Сначала добавьте дом выше.</p>
                    @elseif (! $building_id)
                        <p class="text-sm text-gray-500">Выберите дом во вкладке.</p>
                    @else
                        <form wire:submit="createApartment" class="flex flex-wrap gap-4 items-end max-w-xl">
                            <div class="flex-1 min-w-[8rem]">
                                <x-input-label for="nan" value="Номер квартиры" />
                                <x-text-input wire:model="new_apartment_number" id="nan" class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('new_apartment_number')" class="mt-2" />
                            </div>
                            <x-primary-button type="submit">Добавить квартиру</x-primary-button>
                        </form>
                        <p class="text-base text-slate-700">
                            Полный список с поиском, статусами показаний, приглашениями и доступом —
                            <a href="{{ route('manager.apartments') }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-800">таблица квартир</a>.
                        </p>
                    @endif
                </div>

                <div class="p-6 space-y-4">
                    <h2 class="text-2xl font-bold text-indigo-900">Новый жилец</h2>
                    <p class="text-base text-slate-700">Вы указываете данные и квартиру; пароль жилец задаёт сам по ссылке из письма (с подтверждением). Если письмо не дошло или пароль забыт — отправьте ссылку снова в разделе «Квартиры».</p>
                    @if ($this->buildings->isEmpty() || ! $building_id)
                        <p class="text-sm text-gray-500">Выберите дом во вкладке, затем квартиру из списка этого дома.</p>
                    @else
                        <form wire:submit="createResident" class="grid gap-4 max-w-xl">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="rfn" value="Имя" />
                                    <x-text-input wire:model="resident_first_name" id="rfn" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('resident_first_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="rln" value="Фамилия" />
                                    <x-text-input wire:model="resident_last_name" id="rln" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('resident_last_name')" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="rph" value="Телефон (необязательно)" />
                                <x-text-input wire:model="resident_phone" id="rph" type="tel" class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('resident_phone')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="re" value="Email (логин)" />
                                <x-text-input wire:model="resident_email" id="re" type="email" class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('resident_email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="raid" value="Квартира в этом доме" />
                                <select wire:model="resident_apartment_id" id="raid" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">— выберите —</option>
                                    @foreach ($this->apartmentsForBuilding as $a)
                                        <option value="{{ $a->id }}">кв. {{ $a->number }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('resident_apartment_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-primary-button type="submit">Создать жильца</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
