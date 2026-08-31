<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Πίνακας Κηδεμόνα') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'appointment-booked')
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    ✓ {{ __('Το ραντεβού σας κλείστηκε με επιτυχία.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Τα Παιδιά μου') }}</h3>

                @if ($children->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('Δεν έχετε καταχωρημένα παιδιά. Επικοινωνήστε με τη διοίκηση του σχολείου για να προστεθούν.') }}
                    </p>
                @else
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($children as $child)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="font-medium text-gray-900">{{ $child->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ __('Τάξη') }}: {{ $child->class }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Προσεχή Ραντεβού') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('guardian.book.teachers') }}" class="inline-flex items-center px-6 py-3 bg-[#f2952b] border border-transparent rounded-md font-semibold text-base text-white hover:bg-[#e08419] focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                            {{ __('Κλείσε Ραντεβού') }}
                        </a>
                        <a href="{{ route('guardian.appointments.index') }}" class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-md font-semibold text-base text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Όλα τα Ραντεβού') }}
                        </a>
                        <a href="/user-guides/odigos-kidemona.pdf" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 border border-[#0e6e73] rounded-md font-medium text-sm text-[#0e6e73] hover:bg-[#0e6e73] hover:text-white transition focus:outline-none focus:ring-2 focus:ring-[#0e6e73] focus:ring-offset-2">
                            📄 {{ __('Οδηγός Χρήσης (PDF)') }}
                        </a>
                    </div>
                </div>

                @if ($upcomingAppointments->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">{{ __('Δεν έχετε προσεχή ραντεβού.') }}</p>
                @else
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($upcomingAppointments as $appointment)
                            <div class="py-3 flex items-center justify-between text-sm">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $appointment->teacher->full_name }} &middot; {{ $appointment->child->full_name }}</div>
                                    <div class="text-gray-500">{{ \Illuminate\Support\Carbon::parse($appointment->date)->translatedFormat('d/m/Y') }} στις {{ substr($appointment->start_time, 0, 5) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
