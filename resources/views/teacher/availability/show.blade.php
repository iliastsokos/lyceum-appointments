<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Slots for :date', ['date' => \Illuminate\Support\Carbon::parse($availability->date)->format('l, d/m/Y')]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Done.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> {{ __('Available') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> {{ __('Booked') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span> {{ __('Disabled') }}</span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                    @foreach ($slots as $slot)
                        @php
                            $colors = match ($slot->status->value) {
                                'available' => 'bg-green-100 text-green-800 border-green-300 hover:bg-green-200',
                                'booked' => 'bg-red-100 text-red-800 border-red-300 cursor-not-allowed',
                                'disabled' => 'bg-gray-100 text-gray-500 border-gray-300 hover:bg-gray-200',
                            };
                        @endphp
                        <form method="POST" action="{{ route('teacher.availability.slots.toggle', $slot) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                @disabled($slot->status->value === 'booked')
                                class="w-full text-xs font-medium border rounded-md py-2 px-1 {{ $colors }}"
                                title="{{ $slot->status->label() }}"
                            >
                                {{ substr($slot->start_time, 0, 5) }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
