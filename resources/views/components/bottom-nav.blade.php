@if ($hasBottomNav)
    <nav class="sm:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-outline pb-safe" aria-label="{{ __('Κύρια πλοήγηση') }}">
        <ul class="grid grid-cols-4">
            @foreach ($navTabs as $tab)
                @php($active = request()->routeIs(...$tab['match']))
                <li>
                    <a href="{{ route($tab['route']) }}"
                       @if ($active) aria-current="page" @endif
                       class="flex flex-col items-center justify-center gap-0.5 h-16 text-[11px] font-medium transition {{ $active ? 'text-primary' : 'text-muted' }}">
                        <x-nav-icon :name="$tab['icon']" class="h-6 w-6 {{ $active ? 'stroke-2' : '' }}" />
                        <span class="leading-none text-center px-0.5">{{ $tab['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
