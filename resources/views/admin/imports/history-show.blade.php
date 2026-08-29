<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Λεπτομέρειες Εισαγωγής') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-gray-500">{{ __('Τύπος') }}</dt><dd class="font-medium">{{ $batch->import_type->label() }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Αρχείο') }}</dt><dd class="font-medium">{{ $batch->filename }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Διαχειριστής') }}</dt><dd class="font-medium">{{ $batch->admin->full_name }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Ημερομηνία') }}</dt><dd class="font-medium">{{ $batch->created_at->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Σύνολο γραμμών') }}</dt><dd class="font-medium">{{ $batch->total_rows }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Δημιουργήθηκαν') }}</dt><dd class="font-medium text-green-700">{{ $batch->successful_rows }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Παραλείφθηκαν') }}</dt><dd class="font-medium text-yellow-700">{{ $batch->skipped_rows }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Απέτυχαν') }}</dt><dd class="font-medium text-red-700">{{ $batch->failed_rows }}</dd></div>
                </dl>

                @if ($batch->failed_rows > 0 || $batch->skipped_rows > 0)
                    <div class="mt-4">
                        <a href="{{ route('admin.imports.history.errors', $batch) }}" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Λήψη Αναφοράς Σφαλμάτων (.csv)') }}</a>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="text-sm font-medium text-gray-900 mb-4">{{ __('Λεπτομέρειες Γραμμών') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">{{ __('Γραμμή') }}</th>
                            <th class="py-2 pr-4">{{ __('Πεδίο') }}</th>
                            <th class="py-2 pr-4">{{ __('Μήνυμα') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($errors as $error)
                            <tr>
                                <td class="py-2 pr-4">{{ $error->row_number }}</td>
                                <td class="py-2 pr-4">{{ $error->field }}</td>
                                <td class="py-2 pr-4">{{ $error->error_message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">{{ __('Δεν καταγράφηκαν προβλήματα για αυτή την εισαγωγή.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $errors->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
