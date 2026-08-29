@php($child ??= null)

<div>
    <x-input-label for="first_name" :value="__('First name')" />
    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $child?->first_name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
</div>

<div class="mt-4">
    <x-input-label for="last_name" :value="__('Last name')" />
    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $child?->last_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
</div>

<div class="mt-4">
    <x-input-label for="class" :value="__('Class')" />
    <x-text-input id="class" name="class" type="text" class="mt-1 block w-full" :value="old('class', $child?->class)" required placeholder="e.g. B1" />
    <x-input-error class="mt-2" :messages="$errors->get('class')" />
</div>
