<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Επιβεβαίωση Ραντεβού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('guardian.book.teachers') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&laquo; {{ __('Επιστροφή στη λίστα εκπαιδευτικών') }}</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Επιλέξτε το παιδί σας και επιβεβαιώστε') }}</h3>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Εκπαιδευτικός') }}</dt>
                        <dd class="text-gray-900 font-medium">{{ $teacher->full_name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Ημερομηνία') }}</dt>
                        <dd class="text-gray-900 font-medium">{{ \Illuminate\Support\Carbon::parse($slot->date)->translatedFormat('l, d/m/Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Ώρα') }}</dt>
                        <dd class="text-gray-900 font-medium">{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</dd>
                    </div>
                </dl>

                @if ($errors->any())
                    <div class="mt-4 bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($children->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('Δεν έχετε καταχωρημένα παιδιά. Επικοινωνήστε με τη διοίκηση του σχολείου για να προστεθούν πριν κλείσετε ραντεβού.') }}
                    </p>
                @else
                    <form
                        method="POST"
                        action="{{ route('guardian.book.store', ['teacher' => $teacher, 'slot' => $slot]) }}"
                        class="mt-6"
                        x-data="{ submitting: false }"
                        x-on:submit="submitting = true"
                    >
                        @csrf
                        <x-input-label for="child_id" :value="__('Μαθητής/-τρια')" />
                        <select id="child_id" name="child_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach ($children as $child)
                                <option value="{{ $child->id }}">{{ $child->full_name }} — {{ $child->class }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('child_id')" />

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button x-bind:disabled="submitting">
                                <span x-show="!submitting">{{ __('Επιβεβαίωση Ραντεβού') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Γίνεται κράτηση...') }}</span>
                            </x-primary-button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
