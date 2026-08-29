<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Welcome, :name', ['name' => $teacher->full_name]) }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ __('Subject') }}: {{ $teacher->subject }}</p>
                <a href="{{ route('teacher.availability.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-900">{{ __('Manage My Availability') }} &rarr;</a>
                <p class="mt-4 text-sm text-gray-600">{{ __('Appointments will be available soon.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
