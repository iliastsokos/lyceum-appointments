<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Κηδεμόνες') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status') === 'guardian-created' && session('temporaryPassword'))
                <div class="bg-yellow-50 border border-yellow-300 text-yellow-900 rounded-md p-4 text-sm">
                    {{ __('Ο λογαριασμός κηδεμόνα δημιουργήθηκε. Προσωρινός κωδικός (μοιραστείτε τον με ασφάλεια, δεν θα εμφανιστεί ξανά):') }}
                    <span class="font-mono font-semibold">{{ session('temporaryPassword') }}</span>
                </div>
            @elseif (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Ολοκληρώθηκε.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <form method="GET" class="flex gap-2">
                        <x-text-input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Αναζήτηση κηδεμόνων...') }}" class="w-64" />
                        <x-secondary-button type="submit">{{ __('Αναζήτηση') }}</x-secondary-button>
                    </form>
                    <a href="{{ route('admin.guardians.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Προσθήκη Κηδεμόνα') }}
                    </a>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">{{ __('Όνομα') }}</th>
                                <th class="py-2 pr-4">{{ __('Email') }}</th>
                                <th class="py-2 pr-4">{{ __('Παιδιά') }}</th>
                                <th class="py-2 pr-4">{{ __('Κατάσταση') }}</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($guardians as $guardian)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $guardian->full_name }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $guardian->email }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $guardian->children_count }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $guardian->status->value === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $guardian->status->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-right space-x-3">
                                        <a href="{{ route('admin.guardians.edit', $guardian) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Επεξεργασία') }}</a>
                                        <form method="POST" action="{{ route('admin.guardians.toggle-status', $guardian) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                {{ $guardian->status->value === 'active' ? __('Απενεργοποίηση') : __('Ενεργοποίηση') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">{{ __('Δεν βρέθηκαν κηδεμόνες.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $guardians->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
