<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Προσθήκη Εκπαιδευτικού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.teachers.store') }}">
                    @csrf
                    @include('admin.teachers.partials.form')

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Δημιουργία Εκπαιδευτικού') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
