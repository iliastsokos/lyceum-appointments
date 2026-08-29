<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Επεξεργασία Παιδιού') }}
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
                        <x-primary-button>{{ __('Αποθήκευση Αλλαγών') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">{{ __('Αφαίρεση Παιδιού') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Αυτό θα αφαιρέσει οριστικά αυτό το παιδί από τον λογαριασμό σας.') }}</p>
                <div class="mt-4">
                    <x-confirm-form-button
                        :action="route('guardian.children.destroy', $child)"
                        method="DELETE"
                        :title="__('Αφαίρεση αυτού του παιδιού;')"
                        :message="__('Αυτό θα αφαιρέσει οριστικά τον/την :name από τον λογαριασμό σας. Η ενέργεια δεν αναιρείται.', ['name' => $child->full_name])"
                        :confirm-text="__('Αφαίρεση Παιδιού')"
                        button-class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >{{ __('Αφαίρεση Παιδιού') }}</x-confirm-form-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
