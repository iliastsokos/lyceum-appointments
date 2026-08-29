<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Completed') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                {{ __('Import completed.') }}
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ $type->label() }}</h3>
                <ul class="mt-4 text-sm text-gray-700 space-y-1">
                    <li>{{ $batch->total_rows }} {{ __('rows processed') }}</li>
                    @if ($type->value === 'teachers')
                        <li>{{ $batch->successful_rows }} {{ __('accounts created') }}</li>
                    @else
                        <li>{{ $batch->successful_rows }} {{ __('children associated') }}</li>
                    @endif
                    <li>{{ $batch->skipped_rows }} {{ __('existing account(s) skipped') }}</li>
                    <li>{{ $batch->failed_rows }} {{ __('invalid row(s)') }}</li>
                </ul>

                <div class="mt-6 flex flex-wrap gap-4 text-sm">
                    @if ($hasCredentials)
                        <a href="{{ route('admin.imports.history.credentials', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Download Temporary Passwords (.csv)') }}</a>
                    @endif
                    @if ($batch->failed_rows > 0)
                        <a href="{{ route('admin.imports.history.errors', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Download Error Report') }}</a>
                    @endif
                    <a href="{{ route('admin.imports.show', $type->value) }}" class="text-gray-600 hover:text-gray-900">{{ __('Import Another File') }}</a>
                </div>

                @if ($hasCredentials)
                    <p class="mt-4 text-xs text-gray-500">{{ __('Temporary passwords can only be downloaded once. Share them securely with the account holders — they will be required to change their password on first login.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
