<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Εκπαιδευτικοί') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status') === 'teacher-created' && session('temporaryPassword'))
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-900 rounded-md p-4 text-sm">
                    {{ __('Ο λογαριασμός εκπαιδευτικού δημιουργήθηκε. Προσωρινός κωδικός (μοιραστείτε τον με ασφάλεια, δεν θα εμφανιστεί ξανά):') }}
                    <span class="font-mono font-semibold">{{ session('temporaryPassword') }}</span>
                </div>
            @elseif (session('status') === 'teacher-password-reset' && session('temporaryPassword'))
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-900 rounded-md p-4 text-sm">
                    {{ __('Ο κωδικός επαναφέρθηκε. Νέος προσωρινός κωδικός (μοιραστείτε τον με ασφάλεια, δεν θα εμφανιστεί ξανά):') }}
                    <span class="font-mono font-semibold">{{ session('temporaryPassword') }}</span>
                </div>
            @elseif (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Ολοκληρώθηκε.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 rounded-md p-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <form method="GET" class="flex gap-2">
                        <x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Αναζήτηση εκπαιδευτικών...') }}" class="w-64" />
                        <x-secondary-button type="submit">{{ __('Αναζήτηση') }}</x-secondary-button>
                    </form>
                    <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Προσθήκη Εκπαιδευτικού') }}
                    </a>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">{{ __('Όνομα') }}</th>
                                <th class="py-2 pr-4">{{ __('Email') }}</th>
                                <th class="py-2 pr-4">{{ __('Μάθημα') }}</th>
                                <th class="py-2 pr-4">{{ __('Κατάσταση') }}</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $teacher->full_name }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $teacher->email }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $teacher->subject }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $teacher->status->value === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $teacher->status->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-right space-x-3">
                                        <a href="{{ route('admin.teachers.availability.index', $teacher) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Διαθεσιμότητα') }}</a>
                                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Επεξεργασία') }}</a>
                                        <form method="POST" action="{{ route('admin.teachers.toggle-status', $teacher) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                {{ $teacher->status->value === 'active' ? __('Απενεργοποίηση') : __('Ενεργοποίηση') }}
                                            </button>
                                        </form>
                                        <x-confirm-form-button
                                            :action="route('admin.teachers.reset-password', $teacher)"
                                            method="PATCH"
                                            :title="__('Επαναφορά κωδικού για αυτόν τον εκπαιδευτικό;')"
                                            :message="__('Θα δημιουργηθεί νέος προσωρινός κωδικός και ο τρέχων κωδικός θα πάψει να ισχύει αμέσως. Ο εκπαιδευτικός θα πρέπει να τον αλλάξει στην επόμενη σύνδεση.')"
                                            :confirm-text="__('Επαναφορά')"
                                            button-class="text-gray-600 hover:text-gray-900"
                                        >{{ __('Επαναφορά Κωδικού') }}</x-confirm-form-button>
                                        <x-confirm-form-button
                                            :action="route('admin.teachers.destroy', $teacher)"
                                            method="DELETE"
                                            :title="__('Διαγραφή αυτού του εκπαιδευτικού;')"
                                            :message="__('Αυτή η ενέργεια δεν αναιρείται. Αν ο εκπαιδευτικός έχει ιστορικό διαθεσιμότητας ή ραντεβού, η διαγραφή θα αποτύχει — χρησιμοποιήστε απενεργοποίηση σε αυτή την περίπτωση.')"
                                            :confirm-text="__('Διαγραφή')"
                                            button-class="text-red-600 hover:text-red-900"
                                        >{{ __('Διαγραφή') }}</x-confirm-form-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">{{ __('Δεν βρέθηκαν εκπαιδευτικοί.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $teachers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
