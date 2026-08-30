<nav x-data="{ open: false }" class="bg-[#0e6e73] shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 shrink-0">
                    <span class="relative inline-flex items-center justify-center w-11 h-11 rounded-full bg-white ring-2 ring-[#f2952b]">
                        <span class="text-[#f2952b] font-extrabold text-lg leading-none">1<span class="align-super text-[9px]">ο</span></span>
                        <svg class="absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 text-[#f2952b]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 3 1 8l11 5 9-4.09V17h2V8L12 3Z"/>
                            <path d="M5 10.18v3.64c0 .35.16.68.44.9C6.6 15.5 9 17 12 17s5.4-1.5 6.56-2.28c.28-.22.44-.55.44-.9v-3.64l-7 3.18-7-3.18Z"/>
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-white font-bold text-lg tracking-tight">1ο ΓΕΛ Ραφήνας</span>
                        <span class="block text-[#bfe3e3] text-xs font-medium">Σύστημα Ραντεβού</span>
                    </span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('*.dashboard')">
                        {{ __('Πίνακας Ελέγχου') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-2">
                <x-notification-bell />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-transparent focus:outline-none focus:ring-2 focus:ring-[#f2952b] focus:ring-offset-2 focus:ring-offset-[#0e6e73] transition ease-in-out duration-150">
                            <div>{{ Auth::user()->full_name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('*.dashboard')">
                {{ __('Πίνακας Ελέγχου') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                {{ __('Ειδοποιήσεις') }}
                @php($unread = Auth::user()->notifications()->whereNull('read_at')->count())
                @if ($unread > 0)
                    <span class="ml-1 inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold leading-none align-middle">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-white/15">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->full_name }}</div>
                <div class="font-medium text-sm text-[#bfe3e3]">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Προφίλ') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Αποσύνδεση') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
