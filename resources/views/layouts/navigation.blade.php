<nav class="bg-primary shadow-sm sticky top-0 z-30 pt-safe">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14 sm:h-16">
            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center shrink-0">
                <img src="{{ asset('images/logo-rafin.png') }}" alt="1ο ΓΕΛ Ραφήνας" class="h-8 sm:h-9 w-auto">
            </a>

            <!-- Desktop/tablet nav links -->
            <div class="hidden sm:flex sm:items-center sm:gap-1">
                @foreach ($navTabs as $tab)
                    <x-nav-link :href="route($tab['route'])" :active="request()->routeIs(...$tab['match'])">
                        {{ $tab['label'] }}
                    </x-nav-link>
                @endforeach
            </div>

            <div class="flex items-center gap-1">
                <x-notification-bell />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/15 text-white text-sm font-semibold hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-primary transition ease-in-out duration-150" aria-label="{{ __('Λογαριασμός') }}">
                            {{ mb_substr(Auth::user()->first_name, 0, 1) }}{{ mb_substr(Auth::user()->last_name, 0, 1) }}
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Προφίλ') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Αποσύνδεση') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
