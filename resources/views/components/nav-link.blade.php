@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 pt-1 -mb-px border-b-2 border-[#f2952b] text-sm font-medium leading-5 text-white hover:bg-white/10 rounded-t-md focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-3 pt-1 -mb-px border-b-2 border-transparent text-sm font-medium leading-5 text-white hover:bg-white/10 hover:border-white/40 rounded-t-md focus:outline-none focus:border-white/40 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
