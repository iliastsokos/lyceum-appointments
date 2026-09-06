@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-base text-ink']) }}>
    {{ $value ?? $slot }}
</label>
