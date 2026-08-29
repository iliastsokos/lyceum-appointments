<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">{{ __('Date') }}</th>
                            <th class="py-2 pr-4">{{ __('Type') }}</th>
                            <th class="py-2 pr-4">{{ __('File') }}</th>
                            <th class="py-2 pr-4">{{ __('Admin') }}</th>
                            <th class="py-2 pr-4">{{ __('Total') }}</th>
                            <th class="py-2 pr-4">{{ __('Created') }}</th>
                            <th class="py-2 pr-4">{{ __('Skipped') }}</th>
                            <th class="py-2 pr-4">{{ __('Failed') }}</th>
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
                                    <a href="{{ route('admin.imports.history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-gray-500">{{ __('No imports yet.') }}</td>
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
