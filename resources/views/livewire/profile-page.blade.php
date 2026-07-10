<div>
    <div class="mx-3 mt-3 sm:mx-6">
        <div class="app-card mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Профиль') }}
            </h2>
        </div>
    </div>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @include('profile.forms')
        </div>
    </div>
</div>
