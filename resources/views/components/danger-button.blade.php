<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3.5 bg-red-600 border border-transparent rounded-xl font-semibold text-base text-white shadow-sm hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
