@php
    $cart = $this->cart;
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 lg:py-10">
    <h1 class="text-2xl font-bold lg:text-3xl">Carrito</h1>

    @if ($cart->isEmpty())
        <div class="mt-6 flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-brand-industrial/10">
            <span class="grid size-14 place-items-center rounded-2xl bg-brand-deep text-brand-electric" aria-hidden="true">
                <x-heroicon-o-shopping-cart class="size-7" />
            </span>

            <h2 class="mt-5 text-lg font-bold">Tu carrito está vacío</h2>
            <p class="mt-2 text-brand-industrial">Buscá lo que necesitás en el catálogo y agregalo desde la ficha del producto.</p>

            <div class="mt-6">
                <x-button :href="route('catalog.index')">Ver el catálogo</x-button>
            </div>
        </div>
    @else
        <div class="mt-6 lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8">
            <section aria-labelledby="productos-carrito">
                <h2 id="productos-carrito" class="sr-only">Productos</h2>

                <ul class="space-y-3">
                    @foreach ($cart->lines as $line)
                        @php
                            $product = $line->product;
                            $id = $product->id;
                            $image = $product->coverImageUrl();
                        @endphp

                        <li wire:key="line-{{ $id }}" @class([
                            'rounded-xl bg-white p-3 shadow-sm ring-1 sm:p-4',
                            'ring-brand-industrial/10' => $line->canBeBought(),
                            'ring-2 ring-red-700' => ! $line->canBeBought(),
                        ])>
                            <div class="flex gap-3 sm:gap-4">
                                <a href="{{ route('products.show', $product) }}" class="focus-ring size-20 shrink-0 overflow-hidden rounded-lg bg-brand-neutral sm:size-24" tabindex="-1" aria-hidden="true">
                                    @if ($image)
                                        <img src="{{ $image }}" alt="" class="size-full object-contain p-2 mix-blend-multiply">
                                    @else
                                        <span class="grid size-full place-items-center text-brand-industrial/25"><x-heroicon-o-bolt class="size-8" /></span>
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            @if ($product->brand)
                                                <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-industrial">{{ $product->brand->name }}</p>
                                            @endif

                                            <h3 class="text-sm font-semibold leading-snug">
                                                <a href="{{ route('products.show', $product) }}" class="focus-ring rounded hover:underline">{{ $product->name }}</a>
                                            </h3>

                                            <p class="mt-1 text-xs text-brand-industrial">
                                                SKU {{ $product->sku }} · {{ money($line->unitPrice) }}{{ $product->unit->priceSuffix() }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="remove({{ $id }})"
                                            class="focus-ring -m-1 shrink-0 rounded p-1 text-brand-industrial hover:text-red-700"
                                            aria-label="Quitar {{ $product->name }}"
                                        >
                                            <x-heroicon-o-trash class="size-5" />
                                        </button>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <label for="qty-{{ $id }}" class="sr-only">Cantidad de {{ $product->name }}</label>

                                            <div class="flex h-10 items-stretch overflow-hidden rounded-lg ring-1 ring-brand-industrial/30">
                                                <button type="button" wire:click="decrement({{ $id }})" class="focus-ring grid w-9 place-items-center text-brand-industrial hover:bg-brand-neutral" aria-label="Restar">
                                                    <x-heroicon-o-minus class="size-4" />
                                                </button>

                                                <input
                                                    id="qty-{{ $id }}"
                                                    type="text"
                                                    inputmode="decimal"
                                                    wire:model.live.change="quantities.{{ $id }}"
                                                    @error("quantities.$id") aria-invalid="true" aria-describedby="qty-{{ $id }}-error" @enderror
                                                    class="focus-ring w-16 border-x border-brand-industrial/15 text-center text-base font-semibold lg:text-sm"
                                                >

                                                <button type="button" wire:click="increment({{ $id }})" class="focus-ring grid w-9 place-items-center text-brand-industrial hover:bg-brand-neutral" aria-label="Sumar">
                                                    <x-heroicon-o-plus class="size-4" />
                                                </button>
                                            </div>

                                            @if ($product->unit !== App\Enums\ProductUnit::Unit)
                                                <span class="text-sm text-brand-industrial">{{ mb_strtolower($product->unit->label()) }}s</span>
                                            @endif
                                        </div>

                                        <p class="text-base font-bold">{{ money($line->subtotal) }}</p>
                                    </div>

                                    @error("quantities.$id")
                                        <p id="qty-{{ $id }}-error" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                                    @else
                                        @if ($line->problem)
                                            <p class="mt-2 text-sm font-semibold text-red-700">{{ $line->problem->label() }}</p>
                                        @endif
                                    @enderror
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4">
                    <a href="{{ route('catalog.index') }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Seguir comprando</a>
                </p>
            </section>

            <aside aria-labelledby="resumen" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10 lg:sticky lg:top-6 lg:mt-0">
                <h2 id="resumen" class="text-lg font-bold">Resumen</h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt>Subtotal ({{ trans_choice('{1} :count producto|[2,*] :count productos', $cart->count()) }})</dt>
                        <dd class="font-semibold">{{ money($cart->subtotal) }}</dd>
                    </div>

                    <div class="flex justify-between gap-4 text-brand-industrial">
                        <dt>Envío</dt>
                        <dd>{{ $this->shippingHasFixedCosts ? 'Se calcula con tu dirección' : 'Según tu zona' }}</dd>
                    </div>
                </dl>

                <div class="mt-5">
                    @if ($cart->hasProblems())
                        <p class="mb-3 text-sm font-semibold text-red-700" role="alert">Hay productos que no se pueden comprar así. Revisalos para seguir.</p>

                        <x-button disabled class="w-full cursor-not-allowed opacity-50">Iniciar compra</x-button>
                    @else
                        <x-button :href="route('checkout.delivery')" class="w-full">Iniciar compra</x-button>
                    @endif
                </div>

                <p class="mt-4 flex items-start gap-2 text-xs text-brand-industrial">
                    <x-heroicon-o-truck class="size-4 shrink-0" aria-hidden="true" />
                    Enviamos a Tucumán y todo el NOA.
                </p>
            </aside>
        </div>
    @endif
</div>
