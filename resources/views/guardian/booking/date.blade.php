<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ραντεβού με :name', ['name' => $teacher->full_name]) }}
        </h2>
    </x-slot>

    @php
        $dayAbbr = ['Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ', 'Κυρ'];
        $monthAbbr = ['Ιαν', 'Φεβ', 'Μαρ', 'Απρ', 'Μάι', 'Ιουν', 'Ιουλ', 'Αυγ', 'Σεπ', 'Οκτ', 'Νοε', 'Δεκ'];
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="text-center">
                <a href="{{ route('guardian.book.teachers') }}" class="inline-flex items-center gap-1 py-[10px] px-[15px] rounded-[25px] border border-solid border-[#0e6e73] text-sm font-medium text-[#0e6e73] hover:bg-[#0e6e73] hover:text-white transition">&laquo; {{ __('Επιστροφή στη λίστα εκπαιδευτικών') }}</a>
            </div>

            <div class="bg-white shadow-sm rounded-2xl p-5 sm:p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Διαθέσιμες Ημερομηνίες') }}</h3>

                @if (empty($availableDates))
                    <div class="mt-4 flex items-center gap-3 text-sm text-gray-600 bg-gray-50 rounded-xl p-4">
                        <span class="text-2xl">🗓️</span>
                        <span>{{ __('Ο/Η εκπαιδευτικός δεν έχει ανοίξει διαθέσιμες ημερομηνίες αυτή τη στιγμή. Δοκιμάστε ξανά αργότερα.') }}</span>
                    </div>
                @else
                    <div class="mt-4 -mx-5 sm:-mx-6 px-5 sm:px-6 overflow-x-auto">
                        <div class="flex gap-2.5 pb-1">
                            @foreach ($availableDates as $availableDate)
                                @php
                                    $d = \Illuminate\Support\Carbon::parse($availableDate);
                                    $isSelected = $date === $availableDate;
                                @endphp
                                <a href="{{ route('guardian.book.date', ['teacher' => $teacher, 'date' => $availableDate]) }}"
                                   class="shrink-0 flex flex-col items-center justify-center w-[62px] h-[76px] rounded-2xl border-2 transition
                                          {{ $isSelected
                                                ? 'bg-[#0e6e73] border-[#0e6e73] text-white shadow-md'
                                                : 'bg-white border-gray-200 text-gray-700 hover:border-[#f2952b] hover:bg-orange-50' }}">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide {{ $isSelected ? 'text-[#bfe3e3]' : 'text-gray-400' }}">
                                        {{ $dayAbbr[$d->dayOfWeekIso - 1] }}
                                    </span>
                                    <span class="text-2xl font-bold leading-tight mt-0.5">{{ $d->format('j') }}</span>
                                    <span class="text-[10px] font-medium {{ $isSelected ? 'text-[#bfe3e3]' : 'text-gray-400' }}">
                                        {{ $monthAbbr[$d->month - 1] }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($date)
                <div class="bg-white shadow-sm rounded-2xl p-5 sm:p-6">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        {{ __('Διαθέσιμες ώρες') }} &middot; {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d/m/Y') }}
                    </h3>

                    @if ($slots->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">{{ __('Δεν υπάρχει πλέον διαθεσιμότητα αυτή την ημέρα. Επιλέξτε άλλη ημερομηνία παραπάνω.') }}</p>
                    @else
                        <div class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                            @foreach ($slots as $slot)
                                @if ($slot->status->value === 'available')
                                    <a href="{{ route('guardian.book.confirm', ['teacher' => $teacher, 'slot' => $slot]) }}"
                                       class="flex items-center justify-center min-h-[46px] text-center text-sm font-semibold rounded-xl transition bg-[#0e6e73]/10 text-[#0e6e73] hover:bg-[#0e6e73] hover:text-white">
                                        {{ substr($slot->start_time, 0, 5) }}
                                    </a>
                                @else
                                    <span class="flex items-center justify-center min-h-[46px] text-center text-sm font-medium rounded-xl bg-gray-100 text-gray-400 line-through cursor-not-allowed">
                                        {{ substr($slot->start_time, 0, 5) }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
