<x-layouts::app title="Tu teléfono">
    <section class="mx-auto max-w-md px-4 py-10 lg:py-16">
        <h1 class="text-2xl font-bold lg:text-3xl">Tu teléfono</h1>

        <p class="mt-3 text-brand-industrial">
            Lo usamos solo para coordinar la entrega o el retiro de tus pedidos. Ingresalo con código de área.
        </p>

        <form method="POST" action="{{ route('account.phone.update') }}" class="mt-6 space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-industrial/10">
            @csrf
            @method('PUT')

            <x-input
                name="phone"
                label="Teléfono"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                placeholder="381 4123456"
                :value="old('phone', $user->phone)"
                hint="Escribilo como quieras: 381 4123456, 0381-4123456 o +54 9 381 4123456."
                required
            />

            <div class="flex flex-wrap items-center gap-3">
                <x-button type="submit">Guardar teléfono</x-button>
                <x-button variant="secondary" :href="route('home')">Ahora no</x-button>
            </div>
        </form>
    </section>
</x-layouts::app>
