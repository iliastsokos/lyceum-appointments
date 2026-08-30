<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Τμήματα') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
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
                <h3 class="text-lg font-medium text-gray-900">{{ __('Προσθήκη Τμήματος') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Χρησιμοποιήστε λατινικούς χαρακτήρες και αριθμούς (π.χ. A1), όχι ελληνικούς — χρειάζεται να ταιριάζει ακριβώς με ό,τι πληκτρολογείται στη μαζική εισαγωγή Excel.') }}</p>

                <form method="POST" action="{{ route('admin.school-classes.store') }}" class="mt-4 flex gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Όνομα Τμήματος')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1" value="{{ old('name') }}" maxlength="10" required autofocus />
                    </div>
                    <x-primary-button>{{ __('Προσθήκη') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4">{{ __('Τμήμα') }}</th>
                            <th class="py-2 pr-4">{{ __('Μαθητές') }}</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($schoolClasses as $schoolClass)
                            <tr x-data="{ editing: false }">
                                <td class="py-3 pr-4 font-medium text-gray-900">
                                    <span x-show="! editing">{{ $schoolClass->name }}</span>
                                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.school-classes.update', $schoolClass) }}" class="flex gap-2 items-center">
                                        @csrf
                                        @method('PUT')
                                        <x-text-input type="text" name="name" value="{{ $schoolClass->name }}" maxlength="10" required class="py-1 w-24" />
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs">{{ __('Αποθήκευση') }}</button>
                                        <button type="button" @click="editing = false" class="text-gray-500 hover:text-gray-700 text-xs">{{ __('Ακύρωση') }}</button>
                                    </form>
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $schoolClass->children_count }}</td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <button type="button" x-show="! editing" @click="editing = true" class="text-indigo-600 hover:text-indigo-900">{{ __('Μετονομασία') }}</button>
                                    <x-confirm-form-button
                                        :action="route('admin.school-classes.destroy', $schoolClass)"
                                        method="DELETE"
                                        :title="__('Διαγραφή αυτού του τμήματος;')"
                                        :message="__('Αν υπάρχουν μαθητές σε αυτό το τμήμα, η διαγραφή θα αποτύχει.')"
                                        :confirm-text="__('Διαγραφή')"
                                        button-class="text-red-600 hover:text-red-900"
                                    >{{ __('Διαγραφή') }}</x-confirm-form-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">{{ __('Δεν υπάρχουν ακόμα τμήματα.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
