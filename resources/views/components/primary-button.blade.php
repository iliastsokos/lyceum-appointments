<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3.5 bg-secondary border border-transparent rounded-xl font-semibold text-base text-white shadow-sm hover:bg-secondary-hover focus:bg-secondary-hover active:bg-secondary-active focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 disabled:opacity-60 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
