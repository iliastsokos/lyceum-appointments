<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Πίνακας Διοίκησης') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Εκπαιδευτικοί') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalTeachers }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Κηδεμόνες') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalGuardians }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">{{ __('Μαθητές') }}</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalStudents }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Διαχείριση Χρηστών') }}</h3>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('admin.teachers.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Διαχείριση Εκπαιδευτικών') }}</a>
                    <a href="{{ route('admin.guardians.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Διαχείριση Κηδεμόνων') }}</a>
                    <a href="{{ route('admin.school-classes.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Διαχείριση Τμημάτων') }}</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Μαζική Εισαγωγή') }}</h3>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('admin.imports.index') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Εισαγωγή Εκπαιδευτικών / Κηδεμόνων') }}</a>
                    <a href="{{ route('admin.imports.history') }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Ιστορικό Εισαγωγών') }}</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Συντήρηση Συστήματος') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Χρησιμοποιήστε αυτό μετά από κάθε ενημέρωση της εφαρμογής, ώστε τυχόν αλλαγές στη βάση δεδομένων να εφαρμοστούν — απαραίτητο ειδικά όταν δεν υπάρχει πρόσβαση μέσω SSH/Scheduled Tasks.') }}</p>
                <div class="mt-4">
                    <x-confirm-form-button
                        :action="route('admin.system.migrate')"
                        method="POST"
                        :title="__('Εκτέλεση ενημερώσεων βάσης δεδομένων;')"
                        :message="__('Ασφαλές να τρέξει όσες φορές θέλετε — εφαρμόζει μόνο όσες αλλαγές δεν έχουν ήδη γίνει.')"
                        :confirm-text="__('Εκτέλεση')"
                        button-class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >{{ __('Εκτέλεση Ενημερώσεων Βάσης Δεδομένων') }}</x-confirm-form-button>
                </div>

                @if (session('status') === 'migrations-run')
                    <pre class="mt-4 bg-gray-900 text-gray-100 text-xs rounded-md p-4 overflow-x-auto">{{ session('migrateOutput') }}</pre>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
