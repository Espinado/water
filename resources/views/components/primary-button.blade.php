@php($isManagerApp = app(\App\Services\AppHost::class)->isManager())

<button {{ $attributes->merge(['type' => 'submit', 'class' => $isManagerApp
    ? 'inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-red-600 to-rose-600 border border-transparent rounded-xl font-semibold text-sm text-white tracking-wide shadow-sm hover:from-red-500 hover:to-rose-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150'
    : 'inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-emerald-600 to-green-600 border border-transparent rounded-xl font-semibold text-sm text-white tracking-wide shadow-sm hover:from-emerald-500 hover:to-green-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
