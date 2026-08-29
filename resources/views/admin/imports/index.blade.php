<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Μαζική Εισαγωγή') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('admin.imports.show', 'teachers') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="font-medium text-gray-900">{{ __('Εισαγωγή Εκπαιδευτικών') }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ __('Ανεβάστε ένα αρχείο Excel για μαζική δημιουργία λογαριασμών εκπαιδευτικών.') }}</div>
                </a>
                <a href="{{ route('admin.imports.show', 'guardians') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="font-medium text-gray-900">{{ __('Εισαγωγή Κηδεμόνων') }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ __('Ανεβάστε ένα αρχείο Excel για μαζική δημιουργία λογαριασμών κηδεμόνων και μαθητών.') }}</div>
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <a href="{{ route('admin.imports.history') }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Προβολή Ιστορικού Εισαγωγών') }} &rarr;</a>
            </div>
        </div>
    </div>
</x-app-layout>
