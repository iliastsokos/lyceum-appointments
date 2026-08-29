<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Ο λογαριασμός σας δημιουργήθηκε από τη Διεύθυνση του σχολείου με προσωρινό κωδικό. Παρακαλούμε επιλέξτε νέο κωδικό για να συνεχίσετε.') }}
    </div>

    <form method="POST" action="{{ route('password.force-change.update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="__('Νέος κωδικός')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Επιβεβαίωση νέου κωδικού')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Ορισμός κωδικού') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
