<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Welcome, :name', ['name' => $teacher->full_name]) }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Subject') }}: {{ $teacher->subject }}</p>
                <div class="mt-4 flex gap-4 text-sm">
                    <a href="{{ route('teacher.availability.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Manage My Availability') }}</a>
                    <a href="{{ route('teacher.appointments.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('All Appointments') }}</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Today') }}</h3>

                @if ($nextAppointment)
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Next appointment: :time with :guardian for :child', [
                            'time' => substr($nextAppointment->slot->start_time, 0, 5),
                            'guardian' => $nextAppointment->guardian->full_name,
                            'child' => $nextAppointment->child->full_name,
                        ]) }}
                    </p>
                @endif

                @if ($todaysAppointments->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">{{ __('No appointments scheduled for today.') }}</p>
                @else
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($todaysAppointments as $appointment)
                            <div class="py-3 flex items-center justify-between text-sm">
                                <div>
                                    <div class="font-medium text-gray-900">{{ substr($appointment->slot->start_time, 0, 5) }} &middot; {{ $appointment->guardian->full_name }} &middot; {{ $appointment->child->full_name }}</div>
                                    <div class="text-gray-500">{{ __('Class') }}: {{ $appointment->child->class }}</div>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $appointment->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($recentCancellations->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Recent Cancellations') }}</h3>
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($recentCancellations as $appointment)
                            <div class="py-3 text-sm text-gray-600">
                                {{ \Illuminate\Support\Carbon::parse($appointment->slot->date)->format('d/m/Y') }}
                                &middot; {{ substr($appointment->slot->start_time, 0, 5) }}
                                &middot; {{ $appointment->guardian->full_name }} &middot; {{ $appointment->child->full_name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
