<x-guest-layout>
    <p class="text-sm text-gray-600 mb-4">
        {{ __('Δεν υπάρχει ακόμα κανένας λογαριασμός. Δημιουργήστε τον λογαριασμό διαχειριστή για να ξεκινήσετε.') }}
    </p>

    <form method="POST" action="{{ route('setup-admin.store') }}">
        @csrf

        <div>
            <x-input-label for="first_name" :value="__('Όνομα')" />
            <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="last_name" :value="__('Επώνυμο')" />
            <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Κωδικός Πρόσβασης')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Επιβεβαίωση Κωδικού')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Δημιουργία Λογαριασμού Διαχειριστή') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
