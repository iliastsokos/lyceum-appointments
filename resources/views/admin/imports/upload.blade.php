<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Εισαγωγή :type', ['type' => $type->label()]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Ανέβασμα Αρχείου') }}</h3>
                    <a href="{{ route($type->value === 'teachers' ? 'admin.imports.templates.teachers' : 'admin.imports.templates.guardians') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                        {{ __('Λήψη Προτύπου') }}
                    </a>
                </div>

                <p class="mt-2 text-sm text-gray-600">
                    @if ($type->value === 'teachers')
                        {{ __('Στήλες: first_name, last_name, email, role, subject. Η στήλη role πρέπει να έχει την τιμή "teacher".') }}
                    @else
                        {{ __('Στήλες: guardian_first_name, guardian_last_name, guardian_email, child_first_name, child_last_name, child_class. Επαναλάβετε το ίδιο guardian_email για πολλά παιδιά.') }}
                    @endif
                </p>

                <form
                    method="POST"
                    action="{{ route('admin.imports.preview', $type->value) }}"
                    enctype="multipart/form-data"
                    class="mt-4"
                    x-data="{ submitting: false }"
                    x-on:submit="submitting = true"
                >
                    @csrf
                    <input type="file" name="file" accept=".xlsx" required class="block w-full text-sm text-gray-600" />
                    <p class="mt-1 text-xs text-gray-400">{{ __('Μόνο αρχεία .xlsx, έως 5 MB.') }}</p>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button x-bind:disabled="submitting">
                            <span x-show="!submitting">{{ __('Προεπισκόπηση Εισαγωγής') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Ανεβαίνει...') }}</span>
                        </x-primary-button>
                    </div>
                </form>
            </div>

            @if ($recentBatches->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-900">{{ __('Πρόσφατες Εισαγωγές') }}</h3>
                    <div class="mt-3 divide-y divide-gray-100 text-sm">
                        @foreach ($recentBatches as $batch)
                            <div class="py-2 flex items-center justify-between">
                                <span>{{ $batch->filename }} &middot; {{ $batch->created_at->format('d/m/Y H:i') }}</span>
                                <a href="{{ route('admin.imports.history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Προβολή') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
