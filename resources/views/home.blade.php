<x-layouts::app>
    <section class="bg-brand-deep text-brand-neutral">
        <div class="mx-auto max-w-7xl px-4 py-14 lg:py-24">
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
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 lg:py-16">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-industrial/10 lg:p-8">
            <h2 class="text-xl font-bold">Estamos preparando la tienda</h2>
            <p class="mt-2 max-w-2xl text-brand-industrial">
                Muy pronto vas a poder recorrer el catálogo, armar tu carrito y hacer tus pedidos online.
            </p>
        </div>
    </section>
</x-layouts::app>
