@props([
    'action',
    'method' => 'POST',
    'title',
    'message',
    'confirmText' => __('Επιβεβαίωση'),
    'buttonClass' => 'text-sm text-red-600 hover:text-red-900',
    'confirmButtonClass' => 'inline-flex items-center justify-center px-6 py-3 bg-red-600 border border-transparent rounded-xl font-semibold text-base text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
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
                class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6"
            >
                <h3 id="{{ $dialogId }}-title" class="text-lg font-semibold text-ink">{{ $title }}</h3>
                <p class="mt-2 text-base text-body">{{ $message }}</p>

                <div class="mt-6 flex items-center justify-end gap-4">
                    <button type="button" x-ref="cancelBtn" x-on:click="open = false" class="text-base font-medium text-body hover:text-ink">
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
