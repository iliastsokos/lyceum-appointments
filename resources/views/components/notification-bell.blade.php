<div x-data="{ count: {{ $unreadCount }} }"
     x-init="setInterval(() => {
        fetch('{{ route('notifications.unread-count') }}')
            .then(res => res.json())
            .then(data => { count = data.count; })
            .catch(() => {});
     }, 30000)">
    <x-dropdown align="right" width="w-80">
        <x-slot name="trigger">
            <button class="relative inline-flex items-center p-2 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-[#f2952b] focus:ring-offset-2 focus:ring-offset-[#0e6e73] transition ease-in-out duration-150" aria-label="{{ __('Ειδοποιήσεις') }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <span
                    x-show="count > 0"
                    x-text="count > 9 ? '9+' : count"
                    style="display: none;"
                    class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold leading-none"
                ></span>
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="px-4 py-2 flex items-center justify-between border-b border-gray-100">
                <span class="text-sm font-medium text-gray-700">{{ __('Ειδοποιήσεις') }}</span>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">{{ __('Σήμανση όλων ως αναγνωσμένα') }}</button>
                    </form>
                @endif
            </div>

            @forelse ($recentNotifications as $notification)
                <div class="px-4 py-3 border-b border-gray-50 last:border-b-0 {{ $notification->read_at ? '' : 'bg-indigo-50' }}">
                    <div class="text-sm font-medium text-gray-900">{{ $notification->title }}</div>
                    <div class="text-xs text-gray-600 mt-0.5">{{ $notification->message }}</div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">{{ __('Σήμανση ως αναγνωσμένο') }}</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('Δεν υπάρχουν ειδοποιήσεις ακόμα.') }}</div>
            @endforelse

            <a href="{{ route('notifications.index') }}" class="block text-center px-4 py-2 text-xs text-indigo-600 hover:text-indigo-900 border-t border-gray-100">
                {{ __('Προβολή όλων των ειδοποιήσεων') }}
            </a>
        </x-slot>
    </x-dropdown>
</div>
