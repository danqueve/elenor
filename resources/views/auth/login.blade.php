<x-layouts::app title="Ingresar">
    <section class="mx-auto max-w-md px-4 py-12 lg:py-20">
        <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-brand-industrial/10 lg:p-8">
            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-deep text-brand-electric" aria-hidden="true">
                <x-heroicon-s-bolt class="size-7" />
            </span>

            <h1 class="mt-6 text-2xl font-bold">Ingresá a {{ config('app.name') }}</h1>

            <p class="mt-3 text-brand-industrial">
                Usamos tu cuenta de Google: no hay contraseñas que recordar. Solo accedemos a tu nombre, tu email y tu foto.
            </p>

            @error('google')
                <p class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>
            @enderror

            <div class="mt-8">
                <x-button :href="route('auth.google.redirect')" class="w-full">Continuar con Google</x-button>
            </div>
        </div>
    </section>
</x-layouts::app>
