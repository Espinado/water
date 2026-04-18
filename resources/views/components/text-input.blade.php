@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border-indigo-100 bg-white/95 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}>
