<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Ευχαριστούμε για την εγγραφή! Πριν ξεκινήσετε, επιβεβαιώστε το email σας πατώντας τον σύνδεσμο που μόλις σας στείλαμε. Αν δεν λάβατε το email, ευχαρίστως θα σας στείλουμε ένα ακόμα.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Ένας νέος σύνδεσμος επιβεβαίωσης στάλθηκε στο email που δηλώσατε.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Επανάληψη Αποστολής Email Επιβεβαίωσης') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Αποσύνδεση') }}
            </button>
        </form>
    </div>
</x-guest-layout>
