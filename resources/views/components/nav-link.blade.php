@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1.5 py-2 border-b-2 border-indigo-500 text-base font-semibold leading-5 text-indigo-700 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1.5 py-2 border-b-2 border-transparent text-base font-medium leading-5 text-slate-600 hover:text-indigo-700 hover:border-indigo-300 focus:outline-none focus:text-indigo-700 focus:border-indigo-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
