<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Appointments') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <x-input-label for="date" :value="__('Date')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1" value="{{ request('date') }}" />
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

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                @if ($appointments->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No appointments found.') }}</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">{{ __('Date') }}</th>
                                <th class="py-2 pr-4">{{ __('Time') }}</th>
                                <th class="py-2 pr-4">{{ __('Guardian') }}</th>
                                <th class="py-2 pr-4">{{ __('Student') }}</th>
                                <th class="py-2 pr-4">{{ __('Class') }}</th>
                                <th class="py-2 pr-4">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($appointments as $appointment)
                                <tr>
                                    <td class="py-3 pr-4">{{ \Illuminate\Support\Carbon::parse($appointment->slot->date)->format('d/m/Y') }}</td>
                                    <td class="py-3 pr-4">{{ substr($appointment->slot->start_time, 0, 5) }}</td>
                                    <td class="py-3 pr-4">{{ $appointment->guardian->full_name }}</td>
                                    <td class="py-3 pr-4">{{ $appointment->child->full_name }}</td>
                                    <td class="py-3 pr-4">{{ $appointment->child->class }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            {{ match($appointment->status->value) {
                                                'cancelled' => 'bg-gray-100 text-gray-600',
                                                'completed' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-green-100 text-green-800',
                                            } }}">
                                            {{ $appointment->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
