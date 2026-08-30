<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Επεξεργασία Κηδεμόνα') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-900 rounded-md p-4 text-sm">
                    {{ __('Ολοκληρώθηκε.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.guardians.update', $guardian) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.guardians.partials.form', ['guardian' => $guardian])

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>{{ __('Αποθήκευση Αλλαγών') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900">{{ __('Παιδιά') }}</h3>

                @if ($errors->any())
                    <div class="mt-2 bg-red-50 border border-red-300 text-red-900 rounded-md p-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($guardian->children->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">{{ __('Δεν υπάρχουν καταχωρημένα παιδιά.') }}</p>
                @else
                    <ul class="mt-2 text-sm text-gray-600 divide-y divide-gray-100">
                        @foreach ($guardian->children as $child)
                            <li class="py-2" x-data="{ editing: false }">
                                <div class="flex items-center justify-between" x-show="! editing">
                                    <span>{{ $child->full_name }} — {{ $child->class }}</span>
                                    <div class="flex gap-3 text-xs">
                                        <button type="button" @click="editing = true" class="text-indigo-600 hover:text-indigo-900">{{ __('Επεξεργασία') }}</button>
                                        <x-confirm-form-button
                                            :action="route('admin.guardians.children.destroy', [$guardian, $child])"
                                            method="DELETE"
                                            :title="__('Διαγραφή αυτού του παιδιού;')"
                                            :message="__('Αν το παιδί έχει ιστορικό ραντεβού, η διαγραφή θα αποτύχει.')"
                                            :confirm-text="__('Διαγραφή')"
                                            button-class="text-red-600 hover:text-red-900"
                                        >{{ __('Διαγραφή') }}</x-confirm-form-button>
                                    </div>
                                </div>

                                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.guardians.children.update', [$guardian, $child]) }}" class="flex flex-wrap gap-2 items-end">
                                    @csrf
                                    @method('PUT')
                                    <x-text-input type="text" name="first_name" value="{{ $child->first_name }}" class="py-1 w-24" required />
                                    <x-text-input type="text" name="last_name" value="{{ $child->last_name }}" class="py-1 w-24" required />
                                    <select name="class" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-1" required>
                                        @foreach ($schoolClasses as $class)
                                            <option value="{{ $class }}" @selected($child->class === $class)>{{ $class }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs">{{ __('Αποθήκευση') }}</button>
                                    <button type="button" @click="editing = false" class="text-gray-500 hover:text-gray-700 text-xs">{{ __('Ακύρωση') }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('admin.guardians.children.store', $guardian) }}" class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="first_name" :value="__('Όνομα')" />
                        <x-text-input id="first_name" name="first_name" type="text" class="mt-1" required />
                    </div>
                    <div>
                        <x-input-label for="last_name" :value="__('Επώνυμο')" />
                        <x-text-input id="last_name" name="last_name" type="text" class="mt-1" required />
                    </div>
                    <div>
                        <x-input-label for="class" :value="__('Τάξη')" />
                        <select id="class" name="class" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="" disabled selected>{{ __('Επιλέξτε τάξη') }}</option>
                            @foreach ($schoolClasses as $class)
                                <option value="{{ $class }}">{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit">{{ __('Προσθήκη Παιδιού') }}</x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
