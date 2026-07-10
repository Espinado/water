@php($isManagerApp = app(\App\Services\AppHost::class)->isManager())

<button {{ $attributes->merge(['type' => 'button', 'class' => $isManagerApp
    ? 'inline-flex items-center justify-center px-5 py-3 bg-white/95 border border-red-200 rounded-xl font-semibold text-sm text-red-700 tracking-wide shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150'
    : 'inline-flex items-center justify-center px-5 py-3 bg-white/95 border border-emerald-200 rounded-xl font-semibold text-sm text-emerald-700 tracking-wide shadow-sm hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
