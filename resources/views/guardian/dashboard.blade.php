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
                        <a href="{{ route('guardian.book.teachers') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-secondary border border-transparent rounded-xl font-semibold text-base text-white shadow-sm hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
                            {{ __('Κλείσε Ραντεβού') }}
                        </a>
                        <a href="{{ route('guardian.appointments.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-white border border-primary rounded-xl font-semibold text-base text-primary shadow-sm hover:bg-primary-tint focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            {{ __('Όλα τα Ραντεβού') }}
                        </a>
                        <a href="/user-guides/odigos-kidemona.pdf" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 border border-primary rounded-md font-medium text-sm text-primary hover:bg-primary hover:text-white transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
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
