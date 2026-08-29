<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Guardian Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('My Children') }}</h3>
                    <a href="{{ route('guardian.children.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        {{ __('Add Child') }}
                    </a>
                </div>

                @if ($children->isEmpty())
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('You have not added any children yet. Add a child to start booking appointments.') }}
                    </p>
                @else
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($children as $child)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="font-medium text-gray-900">{{ $child->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ __('Class') }}: {{ $child->class }}</div>
                                <div class="mt-3 flex gap-3 text-sm">
                                    <a href="{{ route('guardian.children.edit', $child) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('My Appointments') }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ __('Appointment booking will be available soon.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
