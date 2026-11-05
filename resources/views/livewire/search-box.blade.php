<div
    class="relative order-last w-full lg:order-none lg:max-w-2xl lg:flex-1"
    x-data="{
        open: false,
        active: -1,
        count: 0,
        init() {
            // Livewire redraws the suggestions while typing: count them again after every change.
            // (Not through x-ref: its registry loses the list when Livewire replaces the node.)
            const sync = () => { this.count = this.options().length; };

            new MutationObserver(sync).observe(this.$root, { childList: true, subtree: true });
            sync();
        },
        options() {
            return Array.from(this.$root.querySelectorAll('[role=option]'));
        },
        move(step) {
            const total = this.options().length;

            if (total === 0) return;

            this.open = true;
            // -1 means 'no suggestion selected': the cursor cycles through it too.
            this.active = (this.active + 1 + step + total + 1) % (total + 1) - 1;
        },
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="open = false; active = -1"
>
    <form action="{{ route('search') }}" method="GET" role="search">
        <label for="header-search" class="sr-only">Buscar productos</label>

        <div class="relative">
            <input
                id="header-search"
                type="search"
                name="q"
                value="{{ $query }}"
                wire:model.live.debounce.250ms="query"
                placeholder="Buscá por nombre o código"
                enterkeyhint="search"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="search-suggestions"
                x-bind:aria-expanded="open && count > 0"
                x-bind:aria-activedescendant="active >= 0 ? 'suggestion-' + active : null"
                x-on:focus="open = true"
                x-on:input="open = true; active = -1"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.enter="if (active >= 0) { $event.preventDefault(); options()[active]?.click(); }"
                class="focus-ring h-11 w-full rounded-lg bg-brand-neutral pl-4 pr-12 text-base text-brand-deep placeholder:text-brand-industrial/70 lg:text-sm"
            >

            <button
                type="submit"
                class="focus-ring absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-lg text-brand-industrial transition-colors hover:text-brand-deep"
                aria-label="Buscar"
            >
                <x-heroicon-o-magnifying-glass class="size-5" />
            </button>
        </div>
    </form>

    @if (mb_strlen(trim($query)) >= 2)
        <div
            id="search-suggestions"
            x-show="open"
            x-cloak
            class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-xl bg-white text-brand-deep shadow-xl ring-1 ring-brand-industrial/15"
        >
            @if ($this->suggestions->isNotEmpty())
                <ul role="listbox" aria-label="Productos sugeridos" class="max-h-[60vh] divide-y divide-brand-industrial/10 overflow-y-auto">
                    @foreach ($this->suggestions as $product)
                        <li role="presentation" wire:key="suggestion-{{ $product->id }}">
                            <a
                                id="suggestion-{{ $loop->index }}"
                                role="option"
                                href="{{ route('products.show', $product) }}"
                                x-bind:aria-selected="active === {{ $loop->index }}"
                                x-bind:class="{ 'bg-brand-neutral': active === {{ $loop->index }} }"
                                x-on:mouseenter="active = {{ $loop->index }}"
                                class="focus-ring flex items-center gap-3 px-4 py-3"
                            >
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $product->name }}</span>
                                    <span class="block truncate text-xs text-brand-industrial">
                                        @if ($product->brand){{ $product->brand->name }} · @endif SKU {{ $product->sku }}
                                    </span>
                                </span>

                                <span class="shrink-0 text-sm font-bold">{{ money($product->priceFor($user)) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a
                    href="{{ route('search', ['q' => trim($query)]) }}"
                    class="focus-ring block border-t border-brand-industrial/10 bg-brand-neutral/60 px-4 py-3 text-center text-sm font-semibold hover:bg-brand-neutral"
                >
                    Ver todos los resultados para «{{ trim($query) }}»
                </a>
            @else
                <p class="px-4 py-4 text-sm text-brand-industrial">
                    No encontramos productos para «{{ trim($query) }}». Probá con otro nombre, marca o código.
                </p>
            @endif
        </div>
    @endif
</div>
