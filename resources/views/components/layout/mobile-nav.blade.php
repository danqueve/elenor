<nav
    aria-label="Navegación principal"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-industrial bg-brand-deep pb-[env(safe-area-inset-bottom)] lg:hidden"
>
    <ul class="grid grid-cols-5">
        @foreach ($items as $item)
            <li>
                <a
                    href="{{ $item['url'] }}"
                    @if ($item['active']) aria-current="page" @endif
                    @class([
                        'focus-ring flex h-16 flex-col items-center justify-center gap-1 text-[10px] font-semibold leading-none tracking-tight transition-colors',
                        'text-brand-electric' => $item['active'],
                        'text-brand-neutral/70 hover:text-brand-neutral' => ! $item['active'],
                    ])
                >
                    <x-dynamic-component :component="'heroicon-'.($item['active'] ? 's' : 'o').'-'.$item['icon']" class="size-6" />
                    <span>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
