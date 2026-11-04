<x-layouts::app title="Mi cuenta">
    <section class="mx-auto max-w-2xl px-4 py-10 lg:py-14">
        <h1 class="text-2xl font-bold lg:text-3xl">Mi cuenta</h1>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-brand-industrial/10">
            <div class="flex items-center gap-4">
                <x-user-avatar :user="$user" class="size-16 text-xl" />

                <div class="min-w-0">
                    <p class="truncate text-lg font-bold">{{ $user->name }}</p>
                    <p class="truncate text-sm text-brand-industrial">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-6 divide-y divide-brand-industrial/10 border-t border-brand-industrial/10 text-sm">
                <div class="flex items-center justify-between gap-4 py-4">
                    <dt class="font-semibold">Teléfono</dt>

                    <dd class="flex items-center gap-3">
                        @if (filled($user->phone))
                            <span>{{ $user->phone }}</span>
                        @else
                            <span class="font-semibold text-red-700">Falta cargarlo</span>
                        @endif

                        <a href="{{ route('account.phone.edit') }}" class="focus-ring rounded font-semibold underline underline-offset-4 hover:text-brand-industrial">
                            {{ filled($user->phone) ? 'Cambiar' : 'Cargar' }}
                        </a>
                    </dd>
                </div>
            </dl>

            @if (blank($user->phone))
                <p class="mt-2 text-sm text-brand-industrial">Lo necesitamos para coordinar la entrega o el retiro de tus pedidos.</p>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf

                <x-button type="submit" variant="secondary">Cerrar sesión</x-button>
            </form>
        </div>
    </section>
</x-layouts::app>
