@php($guardian ??= null)

<div>
    <x-input-label for="first_name" :value="__('First name')" />
    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $guardian?->first_name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
</div>

<div class="mt-4">
    <x-input-label for="last_name" :value="__('Last name')" />
    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $guardian?->last_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
</div>

<div class="mt-4">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $guardian?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div class="mt-4">
    <x-input-label for="phone" :value="__('Phone (optional)')" />
    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $guardian?->phone)" />
    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
</div>

@unless ($guardian)
    <p class="mt-4 text-sm text-gray-500">{{ __('A secure temporary password will be generated automatically. The guardian will be required to change it on first login.') }}</p>
@endunless
