@props(['disabled' => false])

@php($isManagerApp = app(\App\Services\AppHost::class)->isManager())

<input @disabled($disabled) {{ $attributes->merge(['class' => $isManagerApp
    ? 'w-full rounded-xl border-red-100 bg-white/95 px-4 py-3 text-base shadow-sm focus:border-red-500 focus:ring-red-500'
    : 'w-full rounded-xl border-emerald-100 bg-white/95 px-4 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500']) }}>
