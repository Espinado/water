@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-1.5 py-2 border-b-2 border-emerald-500 text-base font-semibold leading-5 text-emerald-700 focus:outline-none focus:border-emerald-700 transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1.5 py-2 border-b-2 border-transparent text-base font-medium leading-5 text-slate-600 hover:text-emerald-700 hover:border-emerald-300 focus:outline-none focus:text-emerald-700 focus:border-emerald-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
