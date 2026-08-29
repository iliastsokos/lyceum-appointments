<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Book an Appointment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ __('Step 1: Select a teacher') }}</h3>

                @if ($teachers->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">{{ __('No teachers are available for booking right now.') }}</p>
                @else
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($teachers as $teacher)
                            <a href="{{ route('guardian.book.date', $teacher) }}" class="block border border-gray-200 rounded-lg p-4 hover:border-indigo-400 hover:shadow-sm transition">
                                <div class="font-medium text-gray-900">{{ $teacher->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $teacher->subject }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
