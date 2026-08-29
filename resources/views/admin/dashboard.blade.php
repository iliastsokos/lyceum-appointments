<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administrator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Teachers') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalTeachers }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Guardians') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalGuardians }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Students') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalStudents }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('User Management') }}</h3>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('admin.teachers.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Manage Teachers') }}</a>
                    <a href="{{ route('admin.guardians.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Manage Guardians') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
