<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ειδοποιήσεις') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($notifications->getCollection()->contains(fn ($n) => $n->read_at === null))
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">{{ __('Σήμανση όλων ως αναγνωσμένα') }}</button>
                    </form>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <div class="p-4 sm:p-6 {{ $notification->read_at ? '' : 'bg-indigo-50' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-medium text-gray-900">{{ $notification->title }}</div>
                                <div class="text-sm text-gray-600 mt-1">{{ $notification->message }}</div>
                                <div class="text-xs text-gray-400 mt-2">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 whitespace-nowrap">{{ __('Σήμανση ως αναγνωσμένο') }}</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-sm text-gray-500 text-center">{{ __('Δεν υπάρχουν ειδοποιήσεις ακόμα.') }}</div>
                @endforelse
            </div>

            <div>
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
