<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login.resident', absolute: false), navigate: false);
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-30 mx-2 mt-2 rounded-2xl border border-white/70 bg-white/90 shadow-sm backdrop-blur sm:mx-4">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-6">
        <div class="flex justify-between min-h-[4.5rem]">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:ms-8 sm:flex sm:items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Кабинет') }}
                    </x-nav-link>
                    @if (auth()->user()->isManager())
                        <x-nav-link :href="app(\App\Services\AppHost::class)->absoluteUrl(\App\Services\AppHost::MANAGER, '/dashboard')" :active="false">
                            {{ __('Панель управляющего') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                <x-language-switcher />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            type="button"
                            @click.stop="dropdownOpen = ! dropdownOpen"
                            class="inline-flex items-center px-3 py-2 border border-emerald-100 text-sm font-medium rounded-xl text-emerald-700 bg-emerald-50/70 hover:text-emerald-800 focus:outline-none transition ease-in-out duration-150"
                        >
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate x-on:click="dropdownOpen = false">
                            {{ __('Профиль') }}
                        </x-dropdown-link>

                        <x-dropdown-button wire:click.stop="logout" x-on:click="dropdownOpen = false">
                            {{ __('Выйти') }}
                        </x-dropdown-button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden py-2">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:bg-emerald-50 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Кабинет') }}
            </x-responsive-nav-link>
            @if (auth()->user()->isManager())
                <x-responsive-nav-link :href="app(\App\Services\AppHost::class)->absoluteUrl(\App\Services\AppHost::MANAGER, '/dashboard')" :active="false">
                    {{ __('Панель управляющего') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 px-4">
                <x-language-switcher inline />
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate x-on:click="open = false">
                    {{ __('Профиль') }}
                </x-responsive-nav-link>

                <button
                    type="button"
                    wire:click.stop="logout"
                    x-on:click="open = false"
                    class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out"
                >
                    {{ __('Выйти') }}
                </button>
            </div>
        </div>
    </div>
</nav>
