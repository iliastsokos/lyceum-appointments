<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Appointments') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'appointment-booked')
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    ✓ {{ __('Your appointment has been successfully booked.') }}
                </div>
            @elseif (session('status') === 'appointment-cancelled')
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('The appointment was successfully cancelled.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <x-input-label for="child_id" :value="__('Child')" />
                        <select id="child_id" name="child_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($children as $child)
                                <option value="{{ $child->id }}" @selected(request('child_id') == $child->id)>{{ $child->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="teacher_id" :value="__('Teacher')" />
                        <select id="teacher_id" name="teacher_id" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['new', 'confirmed', 'cancelled', 'completed'] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($appointments->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No appointments found.') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($appointments as $appointment)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium text-gray-900">{{ $appointment->teacher->full_name }}</div>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ match($appointment->status->value) {
                                            'cancelled' => 'bg-gray-100 text-gray-600',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-green-100 text-green-800',
                                        } }}">
                                        {{ $appointment->status->label() }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-gray-600">{{ __('Student') }}: {{ $appointment->child->full_name }}</div>
                                <div class="mt-1 text-sm text-gray-600">
                                    {{ \Illuminate\Support\Carbon::parse($appointment->slot->date)->format('d/m/Y') }}
                                    &middot; {{ substr($appointment->slot->start_time, 0, 5) }}–{{ substr($appointment->slot->end_time, 0, 5) }}
                                </div>
                                <div class="mt-1 text-xs text-gray-400">{{ __('Booked') }}: {{ $appointment->booked_at->format('d/m/Y H:i') }}</div>

                                @if (in_array($appointment->status->value, ['new', 'confirmed']))
                                    <form method="POST" action="{{ route('guardian.appointments.cancel', $appointment) }}" class="mt-3" onsubmit="return confirm('{{ __('Are you sure you want to cancel this appointment?') }}');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Cancel Appointment') }}</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
