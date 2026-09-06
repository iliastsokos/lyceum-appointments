@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-outline focus:border-primary focus:ring-primary rounded-lg shadow-sm text-base py-2.5 px-3.5']) }}>
