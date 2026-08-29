<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Επεξεργασία Εκπαιδευτικού') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.teachers.partials.form', ['teacher' => $teacher])

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Αποθήκευση Αλλαγών') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
