<x-layouts::app>
    {{-- Banner --}}
    <section class="relative overflow-hidden bg-brand-deep text-brand-neutral">
        <div class="relative mx-auto max-w-7xl px-4 py-14 lg:py-24">
            <p class="text-sm font-semibold uppercase tracking-widest text-brand-electric">Tucumán y el NOA</p>

            <h1 class="mt-3 max-w-3xl text-3xl font-bold leading-tight sm:text-4xl lg:text-6xl">
                Materiales eléctricos y herramientas
            </h1>

            <p class="mt-5 max-w-xl text-base text-brand-neutral/80 lg:text-lg">
                Cables, protección eléctrica, mecanismos, iluminación LED, canalización y herramientas para tu obra o tu taller.
            </p>

            <div class="mt-8">
                <x-button :href="route('catalog.index')">Ver catálogo</x-button>
            </div>
        </div>

        <x-heroicon-s-bolt class="pointer-events-none absolute -right-10 top-1/2 hidden size-[28rem] -translate-y-1/2 text-brand-electric/10 lg:block" aria-hidden="true" />
    </section>

    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-12" aria-labelledby="categorias-titulo">
            <h2 id="categorias-titulo" class="text-xl font-bold lg:text-2xl">Categorías</h2>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($categories as $item)
                    <x-category-card :category="$item->category" :total="$item->total" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($offers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-12" aria-labelledby="ofertas-titulo">
            <div class="flex items-end justify-between gap-4">
                <h2 id="ofertas-titulo" class="text-xl font-bold lg:text-2xl">Ofertas</h2>

                <a href="{{ route('offers.index') }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Ver todas</a>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($offers as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12" aria-labelledby="novedades-titulo">
            <div class="flex items-end justify-between gap-4">
                <h2 id="novedades-titulo" class="text-xl font-bold lg:text-2xl">Novedades</h2>

                <a href="{{ route('catalog.index', ['orden' => 'novedades']) }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Ver todo el catálogo</a>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($latest as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts::app>
