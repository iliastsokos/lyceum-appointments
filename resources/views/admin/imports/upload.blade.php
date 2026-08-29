<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import :type', ['type' => $type->label()]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Upload File') }}</h3>
                    <a href="{{ route($type->value === 'teachers' ? 'admin.imports.templates.teachers' : 'admin.imports.templates.guardians') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                        {{ __('Download Template') }}
                    </a>
                </div>

                <p class="mt-2 text-sm text-gray-600">
                    @if ($type->value === 'teachers')
                        {{ __('Columns: first_name, last_name, email, role, subject. The role column must be "teacher".') }}
                    @else
                        {{ __('Columns: guardian_first_name, guardian_last_name, guardian_email, child_first_name, child_last_name, child_class. Repeat the same guardian_email for multiple children.') }}
                    @endif
                </p>

                <form method="POST" action="{{ route('admin.imports.preview', $type->value) }}" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <input type="file" name="file" accept=".xlsx" required class="block w-full text-sm text-gray-600" />
                    <p class="mt-1 text-xs text-gray-400">{{ __('.xlsx files only, up to 5 MB.') }}</p>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>{{ __('Preview Import') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if ($recentBatches->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-900">{{ __('Recent Imports') }}</h3>
                    <div class="mt-3 divide-y divide-gray-100 text-sm">
                        @foreach ($recentBatches as $batch)
                            <div class="py-2 flex items-center justify-between">
                                <span>{{ $batch->filename }} &middot; {{ $batch->created_at->format('d/m/Y H:i') }}</span>
                                <a href="{{ route('admin.imports.history.show', $batch) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
