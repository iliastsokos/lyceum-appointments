<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Προσθήκη Παιδιού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('guardian.children.store') }}">
                    @csrf
                    @include('guardian.children.partials.form')

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Προσθήκη Παιδιού') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
