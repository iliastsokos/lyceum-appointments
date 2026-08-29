<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Availability') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Done.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Add Availability') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Choose a date and time range. 5-minute appointment slots will be generated automatically.') }}</p>

                <form method="POST" action="{{ route('teacher.availability.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="date" :value="__('Date')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date')" min="{{ today()->toDateString() }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('date')" />
                    </div>
                    <div>
                        <x-input-label for="start_time" :value="__('From')" />
                        <x-text-input id="start_time" name="start_time" type="time" step="300" class="mt-1 block w-full" :value="old('start_time')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('start_time')" />
                    </div>
                    <div>
                        <x-input-label for="end_time" :value="__('To')" />
                        <x-text-input id="end_time" name="end_time" type="time" step="300" class="mt-1 block w-full" :value="old('end_time')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('end_time')" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-primary-button>{{ __('Create Availability') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Upcoming Availability') }}</h3>

                @if ($availabilities->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">{{ __('You have no upcoming availability windows.') }}</p>
                @else
                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($availabilities as $availability)
                            <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        {{ \Illuminate\Support\Carbon::parse($availability->date)->format('l, d/m/Y') }}
                                        &middot;
                                        {{ substr($availability->start_time, 0, 5) }}–{{ substr($availability->end_time, 0, 5) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ __(':available available, :booked booked, :disabled disabled', [
                                            'available' => $availability->available_count,
                                            'booked' => $availability->booked_count,
                                            'disabled' => $availability->disabled_count,
                                        ]) }}
                                    </div>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    <a href="{{ route('teacher.availability.show', $availability) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View Slots') }}</a>
                                    @if ($availability->booked_count === 0)
                                        <x-confirm-form-button
                                            :action="route('teacher.availability.destroy', $availability)"
                                            method="DELETE"
                                            :title="__('Remove this availability window?')"
                                            :message="__('All of its available slots will be removed. This cannot be undone.')"
                                            :confirm-text="__('Remove')"
                                        >{{ __('Remove') }}</x-confirm-form-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
