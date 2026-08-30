<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Πίνακας Κηδεμόνα') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Τα Παιδιά μου') }}</h3>
                    <a href="{{ route('guardian.children.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        {{ __('Προσθήκη Παιδιού') }}
                    </a>
                </div>

                @if ($children->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('Δεν έχετε προσθέσει ακόμα κανένα παιδί. Προσθέστε ένα παιδί για να ξεκινήσετε να κλείνετε ραντεβού.') }}
                    </p>
                @else
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($children as $child)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="font-medium text-gray-900">{{ $child->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ __('Τάξη') }}: {{ $child->class }}</div>
                                <div class="mt-3 flex gap-3 text-sm">
                                    <a href="{{ route('guardian.children.edit', $child) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Επεξεργασία') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Προσεχή Ραντεβού') }}</h3>
                    <div class="flex gap-3">
                        <a href="{{ route('guardian.book.teachers') }}" class="inline-flex items-center px-4 py-2 bg-[#f2952b] border border-transparent rounded-md font-semibold text-sm text-white hover:bg-[#e08419] focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                            {{ __('Κλείσε Ραντεβού') }}
                        </a>
                        <a href="{{ route('guardian.appointments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Προβολή Όλων') }}
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
                                    <div class="text-gray-500">{{ \Illuminate\Support\Carbon::parse($appointment->slot->date)->translatedFormat('d/m/Y') }} στις {{ substr($appointment->slot->start_time, 0, 5) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
