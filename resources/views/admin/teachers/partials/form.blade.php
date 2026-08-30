@php($teacher ??= null)

<div>
    <x-input-label for="first_name" :value="__('Όνομα')" />
    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $teacher?->first_name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
</div>

<div class="mt-4">
    <x-input-label for="last_name" :value="__('Επώνυμο')" />
    <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $teacher?->last_name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
</div>

<div class="mt-4">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $teacher?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div class="mt-4">
    <x-input-label for="subject" :value="__('Ειδικότητα')" />
    <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject', $teacher?->subject)" required placeholder="π.χ. Μαθηματικά" />
    <x-input-error class="mt-2" :messages="$errors->get('subject')" />
</div>

@unless ($teacher)
    <p class="mt-4 text-sm text-gray-500">{{ __('Θα δημιουργηθεί αυτόματα ένας ασφαλής προσωρινός κωδικός. Ο εκπαιδευτικός θα πρέπει να τον αλλάξει στην πρώτη σύνδεση.') }}</p>
@endunless
