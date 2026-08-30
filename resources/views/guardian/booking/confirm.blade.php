<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Επιβεβαίωση Ραντεβού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="text-center">
                <a href="{{ route('guardian.book.teachers') }}" class="inline-flex items-center gap-1 py-[10px] px-[15px] rounded-[25px] border border-solid border-[#0e6e73] text-sm font-medium text-[#0e6e73] hover:bg-[#0e6e73] hover:text-white transition">&laquo; {{ __('Επιστροφή στη λίστα εκπαιδευτικών') }}</a>
            </div>

            <div class="bg-white shadow-sm rounded-2xl p-5 sm:p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Επιλέξτε το παιδί σας και επιβεβαιώστε') }}</h3>

                <dl class="mt-4 space-y-2 text-sm bg-gray-50 rounded-xl p-4">
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
                            <button
                                type="submit"
                                x-bind:disabled="submitting"
                                class="inline-flex items-center px-6 py-3 bg-[#f2952b] border border-transparent rounded-md font-semibold text-base text-white hover:bg-[#e08419] focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 disabled:opacity-60"
                            >
                                <span x-show="!submitting">{{ __('Επιβεβαίωση Ραντεβού') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Γίνεται κράτηση...') }}</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
