<nav aria-label="Categorías" class="hidden border-t border-brand-industrial bg-brand-deep text-brand-neutral lg:block">
    <ul class="mx-auto flex max-w-7xl items-center gap-1 px-4">
        @foreach ($categories as $category)
            @php($active = request('categoria') === $category->slug)

            <li>
                <a
                    href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                    @if ($active) aria-current="page" @endif
                    @class([
                        'focus-ring inline-flex h-11 items-center rounded-lg px-3 text-sm font-semibold transition-colors hover:text-brand-electric',
                        'text-brand-electric' => $active,
                        'text-brand-neutral/90' => ! $active,
                    ])
                >
                    {{ $category->name }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
