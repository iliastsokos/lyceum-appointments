<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Διαθεσιμότητα — :name', ['name' => $teacher->full_name]) }}
        </h2>
    </x-slot>

    @php
        // Restricted to the school's morning shift (08:00–14:10) to keep the
        // dropdown short and on-topic for typical parent-teacher hours.
        $timeOptions = collect(range(8 * 60, 14 * 60 + 10, 5))->map(fn ($m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60));
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.teachers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; {{ __('Πίσω στους Εκπαιδευτικούς') }}</a>

            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Ολοκληρώθηκε.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Προσθήκη Διαθεσιμότητας') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Επιλέξτε ημερομηνία και εύρος ώρας (24ωρη μορφή). Τα πεντάλεπτα ραντεβού δημιουργούνται αυτόματα.') }}</p>

                <form method="POST" action="{{ route('admin.teachers.availability.store', $teacher) }}" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="date" :value="__('Ημερομηνία')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date')" min="{{ today()->toDateString() }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('date')" />
                    </div>
                    <div>
                        <x-input-label for="start_time" :value="__('Από')" />
                        <x-select id="start_time" name="start_time" class="mt-1 block w-full" required>
                            <option value="" disabled @selected(! old('start_time'))>{{ __('Ώρα') }}</option>
                            @foreach ($timeOptions as $t)
                                <option value="{{ $t }}" @selected(old('start_time') === $t)>{{ $t }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error class="mt-2" :messages="$errors->get('start_time')" />
                    </div>
                    <div>
                        <x-input-label for="end_time" :value="__('Έως')" />
                        <x-select id="end_time" name="end_time" class="mt-1 block w-full" required>
                            <option value="" disabled @selected(! old('end_time'))>{{ __('Ώρα') }}</option>
                            @foreach ($timeOptions as $t)
                                <option value="{{ $t }}" @selected(old('end_time') === $t)>{{ $t }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error class="mt-2" :messages="$errors->get('end_time')" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-primary-button>{{ __('Δημιουργία Διαθεσιμότητας') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Προσεχής Διαθεσιμότητα') }}</h3>

                @if ($availabilities->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">{{ __('Δεν υπάρχει προσεχής διαθεσιμότητα.') }}</p>
                @else
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($availabilities as $availability)
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ \Illuminate\Support\Carbon::parse($availability->date)->translatedFormat('l, d/m/Y') }}
                                        &middot;
                                        {{ substr($availability->start_time, 0, 5) }}–{{ substr($availability->end_time, 0, 5) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ __(':available διαθέσιμα, :booked κλεισμένα, :disabled απενεργοποιημένα', [
                                            'available' => $availability->available_count,
                                            'booked' => $availability->booked_count,
                                            'disabled' => $availability->disabled_count,
                                        ]) }}
                                    </div>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    <a href="{{ route('admin.teachers.availability.show', [$teacher, $availability]) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Προβολή Ραντεβού') }}</a>
                                    @if ($availability->booked_count === 0)
                                        <x-confirm-form-button
                                            :action="route('admin.teachers.availability.destroy', [$teacher, $availability])"
                                            method="DELETE"
                                            :title="__('Αφαίρεση αυτού του διαστήματος διαθεσιμότητας;')"
                                            :message="__('Όλα τα διαθέσιμα ραντεβού του θα αφαιρεθούν. Αυτή η ενέργεια δεν αναιρείται.')"
                                            :confirm-text="__('Αφαίρεση')"
                                        >{{ __('Αφαίρεση') }}</x-confirm-form-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
