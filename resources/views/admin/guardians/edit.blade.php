<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Guardian') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.guardians.update', $guardian) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.guardians.partials.form', ['guardian' => $guardian])

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">{{ __('Children') }}</h3>
                @if ($guardian->children->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">{{ __('No children on file.') }}</p>
                @else
                    <ul class="mt-2 text-sm text-gray-600 space-y-1">
                        @foreach ($guardian->children as $child)
                            <li>{{ $child->full_name }} — {{ $child->class }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
