<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Book with :name', ['name' => $teacher->full_name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Step 2: Select a date') }}</h3>

                <form method="GET" action="{{ route('guardian.book.date', $teacher) }}" class="mt-4 flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div>
                        <x-input-label for="date" :value="__('Date')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" value="{{ $date }}" min="{{ today()->toDateString() }}" required />
                    </div>
                    <x-primary-button>{{ __('Show Available Times') }}</x-primary-button>
                </form>
            </div>

            @if ($slots !== null)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Step 3: Select a time') }}</h3>

                    @if ($slots->isEmpty())
                        <p class="mt-4 text-sm text-gray-500">{{ __('No availability on this date. Please choose another date.') }}</p>
                    @else
                        <div class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                            @foreach ($slots as $slot)
                                @if ($slot->status->value === 'available')
                                    <a href="{{ route('guardian.book.confirm', ['teacher' => $teacher, 'slot' => $slot]) }}"
                                       class="text-center text-xs font-medium border rounded-md py-2 px-1 bg-green-100 text-green-800 border-green-300 hover:bg-green-200">
                                        🟢 {{ substr($slot->start_time, 0, 5) }}
                                    </a>
                                @else
                                    <span class="text-center text-xs font-medium border rounded-md py-2 px-1 bg-red-100 text-red-800 border-red-300 cursor-not-allowed">
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
