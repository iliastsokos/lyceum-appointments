@props([
    'action',
    'method' => 'POST',
    'title',
    'message',
    'confirmText' => __('Επιβεβαίωση'),
    'buttonClass' => 'text-sm text-red-600 hover:text-red-900',
    'confirmButtonClass' => 'inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
])

@php($dialogId = 'confirm-dialog-'.\Illuminate\Support\Str::random(8))

<div x-data="{ open: false }" class="inline-block">
    <button
        type="button"
        x-ref="trigger"
        @click="open = true"
        {{ $attributes->merge(['class' => $buttonClass]) }}
    >{{ $slot }}</button>

    <div
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false; $nextTick(() => $refs.trigger.focus())"
        x-effect="if (open) { $nextTick(() => $refs.cancelBtn.focus()) }"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $dialogId }}-title"
    >
        <div class="fixed inset-0 bg-gray-500/75" x-on:click="open = false" aria-hidden="true"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-on:click.outside="open = false"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white rounded-lg shadow-xl max-w-sm w-full p-6"
            >
                <h3 id="{{ $dialogId }}-title" class="text-lg font-medium text-gray-900">{{ $title }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>

                <div class="mt-6 flex items-center justify-end gap-4">
                    <button type="button" x-ref="cancelBtn" x-on:click="open = false" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Ακύρωση') }}
                    </button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if (strtoupper($method) !== 'POST')
                            @method($method)
                        @endif
                        <button type="submit" class="{{ $confirmButtonClass }}">{{ $confirmText }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
