<x-layouts::app :title="$title">
    <section class="mx-auto flex max-w-xl flex-col items-center px-4 py-16 text-center lg:py-24">
        <span class="grid size-14 place-items-center rounded-2xl bg-brand-deep text-brand-electric" aria-hidden="true">
            <x-heroicon-o-wrench-screwdriver class="size-7" />
        </span>

        <h1 class="mt-6 text-2xl font-bold lg:text-3xl">{{ $title }}</h1>

        <p class="mt-3 text-brand-industrial">
            Estamos trabajando en esta sección. Muy pronto vas a poder usarla.
        </p>

        <div class="mt-8">
            <x-button variant="secondary" :href="route('home')">Volver al inicio</x-button>
        </div>
    </section>
</x-layouts::app>
