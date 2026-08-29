<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Child') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('guardian.children.update', $child) }}">
                    @csrf
                    @method('PUT')
                    @include('guardian.children.partials.form', ['child' => $child])

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">{{ __('Remove Child') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('This will permanently remove this child from your account.') }}</p>
                <form method="POST" action="{{ route('guardian.children.destroy', $child) }}" class="mt-4" onsubmit="return confirm('{{ __('Are you sure you want to remove this child?') }}');">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button type="submit">{{ __('Remove Child') }}</x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
