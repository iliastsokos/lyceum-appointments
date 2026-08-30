<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ραντεβού με :name', ['name' => $teacher->full_name]) }}
        </h2>
    </x-slot>

    @php
        $today = today();
        $prevMonth = $monthStart->copy()->subMonthNoOverflow();
        $nextMonth = $monthStart->copy()->addMonthNoOverflow();
        $canGoToPrevMonth = $prevMonth->endOfMonth()->greaterThanOrEqualTo($today->copy()->startOfMonth());

        // Build a Monday-first grid, padding with leading/trailing blanks.
        $leadingBlanks = ($monthStart->dayOfWeekIso - 1);
        $daysInMonth = $monthStart->daysInMonth;
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('guardian.book.teachers') }}" class="inline-flex items-center gap-1 px-4 py-1.5 rounded-[25px] border border-[#0e6e73] text-sm font-medium text-[#0e6e73] hover:bg-[#0e6e73] hover:text-white transition">&laquo; {{ __('Επιστροφή στη λίστα εκπαιδευτικών') }}</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 md:max-w-sm md:mx-auto">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Επιλέξτε ημερομηνία') }}</h3>

                <div class="mt-4 flex items-center justify-between">
                    @if ($canGoToPrevMonth)
                        <a href="{{ route('guardian.book.date', ['teacher' => $teacher, 'month' => $prevMonth->format('Y-m')]) }}"
                           class="p-2 rounded-md text-gray-500 hover:bg-gray-100" aria-label="{{ __('Προηγούμενος μήνας') }}">
                            &laquo;
                        </a>
                    @else
                        <span class="p-2 text-gray-300">&laquo;</span>
                    @endif

                    <div class="font-semibold text-gray-900 capitalize">
                        {{ $monthStart->translatedFormat('F Y') }}
                    </div>

                    <a href="{{ route('guardian.book.date', ['teacher' => $teacher, 'month' => $nextMonth->format('Y-m')]) }}"
                       class="p-2 rounded-md text-gray-500 hover:bg-gray-100" aria-label="{{ __('Επόμενος μήνας') }}">
                        &raquo;
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-400">
                    <div>Δε</div>
                    <div>Τρ</div>
                    <div>Τε</div>
                    <div>Πε</div>
                    <div>Πα</div>
                    <div>Σα</div>
                    <div>Κυ</div>
                </div>

                <div class="mt-1 grid grid-cols-7 gap-1">
                    @for ($i = 0; $i < $leadingBlanks; $i++)
                        <div></div>
                    @endfor

                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $cellDate = $monthStart->copy()->day($day);
                            $cellDateString = $cellDate->toDateString();
                            $isPast = $cellDate->lt($today);
                            $hasAvailability = in_array($cellDateString, $availableDates, true);
                            $isSelected = $date === $cellDateString;
                        @endphp

                        @if ($isPast)
                            <div class="aspect-square flex items-center justify-center rounded-md text-sm text-gray-300">
                                {{ $day }}
                            </div>
                        @else
                            <a href="{{ route('guardian.book.date', ['teacher' => $teacher, 'month' => $monthStart->format('Y-m'), 'date' => $cellDateString]) }}"
                               class="aspect-square flex items-center justify-center rounded-md text-sm font-medium transition
                                      {{ $hasAvailability ? 'bg-[#f2952b] text-white hover:bg-[#e08419]' : 'text-gray-700 hover:bg-gray-100' }}
                                      {{ $isSelected ? 'ring-2 ring-offset-1 ring-[#0e6e73]' : '' }}">
                                {{ $day }}
                            </a>
                        @endif
                    @endfor
                </div>

                <p class="mt-4 text-xs text-gray-500 flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-sm bg-[#f2952b]"></span>
                    {{ __('Ημέρες με διαθέσιμα ραντεβού') }}
                </p>
            </div>

            @if ($slots !== null)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                        {{ __('Διαθέσιμες ώρες για :date', ['date' => \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d/m/Y')]) }}
                    </h3>

                    @if ($slots->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">{{ __('Δεν υπάρχει διαθεσιμότητα αυτή την ημέρα. Επιλέξτε άλλη ημερομηνία από το ημερολόγιο.') }}</p>
                    @else
                        <div class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                            @foreach ($slots as $slot)
                                @if ($slot->status->value === 'available')
                                    <a href="{{ route('guardian.book.confirm', ['teacher' => $teacher, 'slot' => $slot]) }}"
                                       class="flex items-center justify-center min-h-11 text-center text-xs font-medium border rounded-md py-2 px-1 bg-green-100 text-green-800 border-green-300 hover:bg-green-200">
                                        🟢 {{ substr($slot->start_time, 0, 5) }}
                                    </a>
                                @else
                                    <span class="flex items-center justify-center min-h-11 text-center text-xs font-medium border rounded-md py-2 px-1 bg-red-100 text-red-800 border-red-300 cursor-not-allowed">
                                        🔴 {{ substr($slot->start_time, 0, 5) }}
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
