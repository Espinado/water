<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login', absolute: false), navigate: false);
    }
}; ?>

<header class="sticky top-0 z-30 border-b border-k16-border bg-k16-surface">
    <div class="flex min-h-[4rem] items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('manager.dashboard') }}" wire:navigate class="shrink-0">
                <x-application-logo class="block h-8 w-auto fill-current text-k16-text" />
            </a>
            <p class="hidden truncate text-base font-semibold text-k16-text-muted sm:block">
                {{ config('pwa.apps.manager.name', 'K16 Pro') }}
            </p>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <x-language-switcher />
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="inline-flex min-h-touch items-center gap-2 rounded-xl border border-k16-border bg-k16-bg px-3 text-base font-medium text-k16-text">
                        <span class="max-w-[8rem] truncate sm:max-w-[12rem]" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                        <svg class="h-4 w-4 shrink-0 text-k16-text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Профиль') }}
                    </x-dropdown-link>
                    <button wire:click="logout" type="button" class="w-full text-start">
                        <x-dropdown-link>{{ __('Выйти') }}</x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
