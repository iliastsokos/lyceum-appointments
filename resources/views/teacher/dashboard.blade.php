<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Πίνακας Εκπαιδευτικού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Καλώς ήρθατε, :name', ['name' => $teacher->full_name]) }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Ειδικότητα') }}: {{ $teacher->subject }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('teacher.availability.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-secondary border border-transparent rounded-xl font-semibold text-base text-white shadow-sm hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
                        {{ __('Διαχείριση Διαθεσιμότητάς μου') }}
                    </a>
                    <a href="{{ route('teacher.appointments.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-white border border-primary rounded-xl font-semibold text-base text-primary shadow-sm hover:bg-primary-tint focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        {{ __('Όλα τα Ραντεβού') }}
                    </a>
                    <a href="/user-guides/odigos-ekpaideftikou.pdf" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 border border-primary rounded-md font-medium text-sm text-primary hover:bg-primary hover:text-white transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        📄 {{ __('Οδηγός Χρήσης (PDF)') }}
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Σήμερα') }}</h3>

                @if ($nextAppointment)
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Επόμενο ραντεβού: :time με :guardian για :child', [
                            'time' => substr($nextAppointment->start_time, 0, 5),
                            'guardian' => $nextAppointment->guardian->full_name,
                            'child' => $nextAppointment->child->full_name,
                        ]) }}
                    </p>
                @endif

                @if ($todaysAppointments->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">{{ __('Δεν έχετε προγραμματισμένα ραντεβού σήμερα.') }}</p>
                @else
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($todaysAppointments as $appointment)
                            <div class="py-3 flex items-center justify-between text-sm">
                                <div>
                                    <div class="font-medium text-gray-900">{{ substr($appointment->start_time, 0, 5) }} &middot; {{ $appointment->guardian->full_name }} &middot; {{ $appointment->child->full_name }}</div>
                                    <div class="text-gray-500">{{ __('Τάξη') }}: {{ $appointment->child->class }}</div>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $appointment->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($recentCancellations->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Πρόσφατες Ακυρώσεις') }}</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($recentCancellations as $appointment)
                            <div class="py-3 text-sm text-gray-600">
                                {{ \Illuminate\Support\Carbon::parse($appointment->date)->translatedFormat('d/m/Y') }}
                                &middot; {{ substr($appointment->start_time, 0, 5) }}
                                &middot; {{ $appointment->guardian->full_name }} &middot; {{ $appointment->child->full_name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
