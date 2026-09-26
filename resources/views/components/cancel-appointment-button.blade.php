@props([
    'action',
    'message',
])

@php($reasonId = 'cancel-reason-'.\Illuminate\Support\Str::random(8))

<x-confirm-form-button
    :action="$action"
    method="PATCH"
    :title="__('Ακύρωση αυτού του ραντεβού;')"
    :message="$message"
    :confirm-text="__('Ακύρωση Ραντεβού')"
    :dismiss-text="__('Πίσω')"
    {{ $attributes }}
>
    {{ __('Ακύρωση Ραντεβού') }}

    <x-slot:fields>
        <label for="{{ $reasonId }}" class="block text-sm font-medium text-body">{{ __('Αιτιολογία (προαιρετικά)') }}</label>
        <textarea
            id="{{ $reasonId }}"
            name="reason"
            rows="2"
            maxlength="500"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
        ></textarea>
    </x-slot:fields>
</x-confirm-form-button>
