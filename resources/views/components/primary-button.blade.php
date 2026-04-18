<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-indigo-600 to-fuchsia-600 border border-transparent rounded-xl font-semibold text-sm text-white tracking-wide shadow-sm hover:from-indigo-500 hover:to-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
