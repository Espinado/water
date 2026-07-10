<x-manager-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-k16-text">
            {{ __('Профиль') }}
        </h2>
    </x-slot>

    <div class="manager-mobile-pad py-6 sm:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('profile.forms')
        </div>
    </div>
</x-manager-layout>
