<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Preview Import: :type', ['type' => $type->label()]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $summary['total'] }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Rows detected') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-green-700">{{ $summary['valid'] }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Valid') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-yellow-700">{{ $summary['skip'] }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Existing (skipped)') }}</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-red-700">{{ $summary['error'] }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Errors') }}</div>
                    </div>
                </div>

                @if ($type->value === 'guardians')
                    <p class="mt-4 text-sm text-gray-600 text-center">
                        {{ __(':new new guardian account(s), :existing existing guardian(s) detected.', ['new' => $summary['guardians_new'], 'existing' => $summary['guardians_existing']]) }}
                    </p>
                @endif

                @php($nothingToImport = $summary['valid'] === 0)
                <form
                    method="POST"
                    action="{{ route('admin.imports.commit', $type->value) }}"
                    class="mt-6 flex items-center justify-center gap-4"
                    x-data="{ submitting: false }"
                    x-on:submit="submitting = true"
                >
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />
                    <a href="{{ route('admin.imports.show', $type->value) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button x-bind:disabled="submitting || {{ $nothingToImport ? 'true' : 'false' }}">
                        <span x-show="!submitting">{{ __('Confirm Import') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Importing...') }}</span>
                    </x-primary-button>
                </form>
            </div>

            @if ($summary['error'] > 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <h3 class="text-sm font-medium text-gray-900 mb-4">{{ __('Errors') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">{{ __('Row') }}</th>
                                <th class="py-2 pr-4">{{ __('Field') }}</th>
                                <th class="py-2 pr-4">{{ __('Error') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows->where('status', 'error') as $row)
                                @foreach ($row['errors'] as $field => $message)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $row['row_number'] }}</td>
                                        <td class="py-2 pr-4">{{ $field }}</td>
                                        <td class="py-2 pr-4">{{ $message }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($summary['skip'] > 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <h3 class="text-sm font-medium text-gray-900 mb-4">{{ __('Existing accounts (will be skipped)') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">{{ __('Row') }}</th>
                                <th class="py-2 pr-4">{{ __('Email') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows->where('status', 'skip') as $row)
                                <tr>
                                    <td class="py-2 pr-4">{{ $row['row_number'] }}</td>
                                    <td class="py-2 pr-4">{{ $row['data']['email'] ?? $row['data']['guardianEmail'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
