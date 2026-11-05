<div
    x-data="{ filtersOpen: false }"
    x-on:keydown.escape.window="filtersOpen = false"
    class="mx-auto max-w-7xl px-4 py-6 lg:py-10"
>
    <x-breadcrumb :items="$breadcrumb" />

    <h1 class="mt-3 text-2xl font-bold lg:text-3xl">{{ $heading }}</h1>

    @if (trim($q) !== '')
        <p class="mt-2 text-sm text-brand-industrial">
            <button type="button" wire:click="clearSearch" class="focus-ring rounded font-semibold underline underline-offset-4 hover:text-brand-deep">Quitar la búsqueda</button>
        </p>
    @endif

    <div class="mt-6 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start lg:gap-8">
        {{-- Filters: a sidebar on desktop, a full-screen panel on phones. --}}
        <aside
            id="filtros"
            aria-label="Filtros"
            class="hidden space-y-4 max-lg:fixed max-lg:inset-0 max-lg:z-50 max-lg:overflow-y-auto max-lg:bg-brand-neutral max-lg:p-4 lg:block"
            x-bind:class="{ 'max-lg:block': filtersOpen }"
            x-trap.noscroll="filtersOpen"
        >
            <div class="flex items-center justify-between lg:hidden">
                <h2 class="text-lg font-bold">Filtros</h2>

                <button type="button" class="focus-ring grid size-10 place-items-center rounded-lg" x-on:click="filtersOpen = false" aria-label="Cerrar filtros">
                    <x-heroicon-o-x-mark class="size-6" />
                </button>
            </div>

            @if ($navigation['items']->isNotEmpty())
                <nav aria-label="Categorías" class="rounded-xl bg-white p-4 ring-1 ring-brand-industrial/10">
                    <h3 class="text-sm font-bold uppercase tracking-wider">
                        @if ($navigation['parent'] && $navigation['parent']->isNot($category))
                            <a href="{{ route('categories.show', $navigation['parent']) }}" class="focus-ring rounded hover:underline">{{ $navigation['title'] }}</a>
                        @else
                            {{ $navigation['title'] }}
                        @endif
                    </h3>

                    <ul class="mt-3 space-y-0.5">
                        @foreach ($navigation['items'] as $item)
                            @php($current = $category?->is($item->category))

                            <li>
                                <a
                                    href="{{ route('categories.show', $item->category) }}"
                                    @if ($current) aria-current="page" @endif
                                    @class([
                                        'focus-ring flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-brand-neutral',
                                        'font-bold' => $current,
                                    ])
                                >
                                    <span>{{ $item->category->name }}</span>
                                    <span class="text-brand-industrial">{{ $item->total }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($brandFacet->isNotEmpty())
                <section x-data="{ open: true }" class="rounded-xl bg-white ring-1 ring-brand-industrial/10">
                    <h3>
                        <button type="button" class="focus-ring flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-bold uppercase tracking-wider" x-on:click="open = ! open" x-bind:aria-expanded="open">
                            Marca
                            <x-heroicon-o-chevron-down class="size-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                    </h3>

                    <ul x-show="open" x-collapse class="space-y-2 px-4 pb-4">
                        @foreach ($brandFacet as $brand)
                            <li wire:key="brand-{{ $brand->id }}">
                                <label class="flex cursor-pointer items-center gap-3 text-sm">
                                    <input type="checkbox" wire:model.live="brands" value="{{ $brand->id }}" class="size-4 shrink-0 accent-brand-deep">
                                    <span class="flex-1">{{ $brand->name }}</span>
                                    <span class="text-brand-industrial">{{ $brand->total }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($priceBounds !== null)
                <section x-data="{ open: true }" class="rounded-xl bg-white ring-1 ring-brand-industrial/10">
                    <h3>
                        <button type="button" class="focus-ring flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-bold uppercase tracking-wider" x-on:click="open = ! open" x-bind:aria-expanded="open">
                            Precio
                            <x-heroicon-o-chevron-down class="size-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                    </h3>

                    <div x-show="open" x-collapse class="px-4 pb-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="precio-min" class="sr-only">Precio mínimo</label>
                                <input
                                    id="precio-min"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    wire:model.live.debounce.700ms="min"
                                    placeholder="Desde"
                                    class="focus-ring h-10 w-full rounded-lg bg-white px-3 text-base ring-1 ring-brand-industrial/30 lg:text-sm"
                                >
                            </div>

                            <div>
                                <label for="precio-max" class="sr-only">Precio máximo</label>
                                <input
                                    id="precio-max"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    wire:model.live.debounce.700ms="max"
                                    placeholder="Hasta"
                                    class="focus-ring h-10 w-full rounded-lg bg-white px-3 text-base ring-1 ring-brand-industrial/30 lg:text-sm"
                                >
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-brand-industrial">
                            Entre {{ money($priceBounds['min'], 0) }} y {{ money($priceBounds['max'], 0) }}
                        </p>
                    </div>
                </section>
            @endif

            @foreach ($specFacets as $facet)
                <section x-data="{ open: true, all: false }" class="rounded-xl bg-white ring-1 ring-brand-industrial/10" wire:key="facet-{{ $facet['name'] }}">
                    <h3>
                        <button type="button" class="focus-ring flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-bold uppercase tracking-wider" x-on:click="open = ! open" x-bind:aria-expanded="open">
                            {{ $facet['name'] }}
                            <x-heroicon-o-chevron-down class="size-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                    </h3>

                    <div x-show="open" x-collapse class="px-4 pb-4">
                        <ul class="space-y-2">
                            @foreach ($facet['values'] as $option)
                                <li wire:key="spec-{{ $facet['name'] }}-{{ $option['value'] }}" @if ($loop->index >= 6) x-show="all" @endif>
                                    <label class="flex cursor-pointer items-center gap-3 text-sm">
                                        <input
                                            type="checkbox"
                                            value="{{ $option['value'] }}"
                                            @checked(in_array($option['value'], $filters->specs[$facet['name']] ?? [], true))
                                            x-bind:checked="($wire.specs[@js($facet['name'])] ?? []).includes(@js($option['value']))"
                                            wire:click="toggleSpec(@js($facet['name']), @js($option['value']))"
                                            class="size-4 shrink-0 accent-brand-deep"
                                        >
                                        <span class="flex-1">{{ $option['value'] }}</span>
                                        <span class="text-brand-industrial">{{ $option['total'] }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>

                        @if (count($facet['values']) > 6)
                            <button type="button" class="focus-ring mt-3 rounded text-sm font-semibold underline underline-offset-4" x-on:click="all = ! all" x-text="all ? 'Ver menos' : 'Ver las {{ count($facet['values']) }} opciones'"></button>
                        @endif
                    </div>
                </section>
            @endforeach

            @if ($chips !== [])
                <button type="button" wire:click="clearFilters" class="focus-ring w-full rounded-lg px-4 py-2.5 text-sm font-semibold underline underline-offset-4">
                    Limpiar todos los filtros
                </button>
            @endif

            {{-- Phones only: close the panel and see the results. --}}
            <div class="sticky bottom-0 -mx-4 border-t border-brand-industrial/15 bg-brand-neutral p-4 lg:hidden">
                <x-button type="button" class="w-full" x-on:click="filtersOpen = false">
                    Ver {{ trans_choice('{0} los resultados|{1} 1 resultado|[2,*] :count resultados', $products->total()) }}
                </x-button>
            </div>
        </aside>

        <section id="resultados" class="min-w-0" aria-labelledby="resultados-titulo">
            <h2 id="resultados-titulo" class="sr-only">Resultados</h2>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-brand-industrial" role="status" aria-live="polite">
                    {{ trans_choice('{0} Sin resultados|{1} 1 producto|[2,*] :count productos', $products->total()) }}
                </p>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="focus-ring inline-flex h-11 items-center gap-2 rounded-lg bg-white px-4 text-sm font-semibold ring-1 ring-brand-industrial/20 lg:hidden"
                        x-on:click="filtersOpen = true"
                        aria-controls="filtros"
                    >
                        <x-heroicon-o-funnel class="size-5" />
                        Filtrar

                        @if ($filters->activeFilterCount() > 0)
                            <span class="grid min-w-5 place-items-center rounded-full bg-brand-deep px-1 text-[11px] font-bold leading-5 text-brand-neutral">{{ $filters->activeFilterCount() }}</span>
                        @endif
                    </button>

                    <label for="orden" class="sr-only">Ordenar por</label>
                    <select id="orden" wire:model.live="sort" class="focus-ring h-11 rounded-lg bg-white px-3 text-base font-semibold ring-1 ring-brand-industrial/20 lg:text-sm">
                        <option value="">{{ $defaultSort->label() }}</option>

                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($chips !== [])
                <ul class="mt-4 flex flex-wrap items-center gap-2" aria-label="Filtros activos">
                    @foreach ($chips as $chip)
                        <li wire:key="chip-{{ $loop->index }}-{{ $chip['label'] }}">
                            <button type="button" wire:click="{{ $chip['action'] }}" class="focus-ring inline-flex items-center gap-1.5 rounded-full bg-white py-1.5 pl-3 pr-2 text-xs font-semibold ring-1 ring-brand-industrial/20 transition-colors hover:bg-brand-neutral">
                                {{ $chip['label'] }}
                                <x-heroicon-o-x-mark class="size-4" />
                                <span class="sr-only">Quitar este filtro</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div wire:loading.class="opacity-50" class="transition-opacity">
                @if ($products->isEmpty())
                    <div class="mt-6 rounded-2xl bg-white p-8 text-center ring-1 ring-brand-industrial/10">
                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-neutral text-brand-industrial" aria-hidden="true">
                            <x-heroicon-o-magnifying-glass class="size-7" />
                        </span>

                        <h3 class="mt-4 text-lg font-bold">No encontramos productos</h3>

                        <p class="mx-auto mt-2 max-w-md text-brand-industrial">
                            {{ trim($q) !== '' ? 'Probá con otro nombre, marca o código, o quitá algún filtro.' : 'Probá quitando algún filtro.' }}
                        </p>

                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            @if ($chips !== [])
                                <x-button type="button" variant="secondary" wire:click="clearFilters">Limpiar filtros</x-button>
                            @endif

                            @if ($whatsapp)
                                <x-button variant="secondary" :href="$whatsapp" target="_blank" rel="noopener">Consultanos por WhatsApp</x-button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-8">
                {{ $products->links(data: ['scrollTo' => '#resultados']) }}
            </div>
        </section>
    </div>
</div>
