<x-layouts::app title="Mis pedidos">
    <section class="mx-auto max-w-3xl px-4 py-6 lg:py-10">
        <h1 class="text-2xl font-bold lg:text-3xl">Mis pedidos</h1>

        @if ($orders->isEmpty())
            <div class="mt-6 flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-brand-industrial/10">
                <span class="grid size-14 place-items-center rounded-2xl bg-brand-deep text-brand-electric" aria-hidden="true">
                    <x-heroicon-o-shopping-bag class="size-7" />
                </span>

                <h2 class="mt-5 text-lg font-bold">Todavía no hiciste pedidos</h2>
                <p class="mt-2 text-brand-industrial">Cuando compres, vas a poder seguirlos desde acá.</p>

                <div class="mt-6">
                    <x-button :href="route('catalog.index')">Ver el catálogo</x-button>
                </div>
            </div>
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($orders as $order)
                    <li class="relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-brand-industrial/10 transition hover:shadow-md">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-bold">
                                    <a href="{{ route('orders.show', $order) }}" class="focus-ring rounded after:absolute after:inset-0">Pedido {{ $order->number }}</a>
                                </h2>
                                <p class="mt-0.5 text-sm text-brand-industrial">
                                    {{ $order->created_at->translatedFormat('j \d\e F \d\e Y') }} · {{ trans_choice('{1} :count producto|[2,*] :count productos', $order->items_count) }}
                                </p>
                            </div>

                            <x-order-status-badge :status="$order->status" />
                        </div>

                        <p class="mt-3 text-lg font-bold">{{ money($order->total) }}</p>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</x-layouts::app>
