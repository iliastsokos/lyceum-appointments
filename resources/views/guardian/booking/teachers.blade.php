<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Κλείσιμο Ραντεβού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Επιλέξτε εκπαιδευτικό') }}</h3>

                @if ($teachers->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">{{ __('Δεν υπάρχουν διαθέσιμοι εκπαιδευτικοί για κράτηση αυτή τη στιγμή.') }}</p>
                @else
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            {{ __('Έχει διαθέσιμα ραντεβού') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                            {{ __('Δεν έχει ανοίξει διαθέσιμες ημερομηνίες αυτή τη στιγμή') }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($teachers as $teacher)
                            @php
                                $hasAvailability = in_array($teacher->id, $teacherIdsWithAvailability, true);
                            @endphp
                            <a href="{{ route('guardian.book.date', $teacher) }}"
                               class="block border rounded-lg p-4 hover:shadow-sm transition
                                      {{ $hasAvailability
                                            ? 'bg-green-50 border-green-300 hover:border-green-400'
                                            : 'bg-gray-50 border-gray-200 hover:border-gray-300' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $teacher->full_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $teacher->subject }}</div>
                                    </div>
                                    <span class="shrink-0 w-2.5 h-2.5 mt-1 rounded-full {{ $hasAvailability ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
