<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ιστορικό Εισαγωγών') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">{{ __('Ημερομηνία') }}</th>
                            <th class="py-2 pr-4">{{ __('Τύπος') }}</th>
                            <th class="py-2 pr-4">{{ __('Αρχείο') }}</th>
                            <th class="py-2 pr-4">{{ __('Διαχειριστής') }}</th>
                            <th class="py-2 pr-4">{{ __('Σύνολο') }}</th>
                            <th class="py-2 pr-4">{{ __('Δημιουργήθηκαν') }}</th>
                            <th class="py-2 pr-4">{{ __('Παραλείφθηκαν') }}</th>
                            <th class="py-2 pr-4">{{ __('Απέτυχαν') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="py-2 pr-4">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 pr-4">{{ $batch->import_type->label() }}</td>
                                <td class="py-2 pr-4">{{ $batch->filename }}</td>
                                <td class="py-2 pr-4">{{ $batch->admin->full_name }}</td>
                                <td class="py-2 pr-4">{{ $batch->total_rows }}</td>
                                <td class="py-2 pr-4">{{ $batch->successful_rows }}</td>
                                <td class="py-2 pr-4">{{ $batch->skipped_rows }}</td>
                                <td class="py-2 pr-4">{{ $batch->failed_rows }}</td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('admin.imports.history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Προβολή') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-gray-500">{{ __('Δεν υπάρχουν εισαγωγές ακόμα.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
