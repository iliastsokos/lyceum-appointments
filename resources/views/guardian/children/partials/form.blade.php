@php($child ??= null)

<div>
    <x-input-label for="first_name" :value="__('Όνομα')" />
    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $child?->first_name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
</div>

<div class="mt-4">
    <x-input-label for="last_name" :value="__('Επώνυμο')" />
    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $child?->last_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
</div>

<div class="mt-4">
    <x-input-label for="class" :value="__('Τάξη')" />
    <select id="class" name="class" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
        <option value="" disabled @selected(old('class', $child?->class) === null)>{{ __('Επιλέξτε τάξη') }}</option>
        @foreach (\App\Enums\SchoolClass::values() as $class)
            <option value="{{ $class }}" @selected(old('class', $child?->class) === $class)>{{ $class }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('class')" />
</div>
