<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Ξεχάσατε τον κωδικό σας; Κανένα πρόβλημα. Απλώς πείτε μας το email σας και θα σας στείλουμε έναν σύνδεσμο επαναφοράς κωδικού.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Αποστολή Συνδέσμου Επαναφοράς') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
