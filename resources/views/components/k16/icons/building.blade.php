@props(['class' => 'h-6 w-6'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M3.75 21V7.5a2.25 2.25 0 012.25-2.25h4.5A2.25 2.25 0 0112.75 7.5V21M3.75 10.5h4.5M3.75 14.25h4.5M12.75 10.5h4.5M12.75 14.25h4.5M12.75 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
</svg>
