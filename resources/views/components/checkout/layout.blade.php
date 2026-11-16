@props(['step', 'title', 'cart', 'shipping' => 'unknown', 'total' => null])

{{--
    The frame of the three checkout steps: the step indicator, the step
    itself ($slot) and the order summary on the side.
    shipping: "unknown" (no address yet), "to_agree" (the zone has no fixed cost) or the amount.
    (Not null for "to agree": Blade treats a null prop as missing and uses the default.)
--}}
@php
    $steps = [1 => 'Entrega', 2 => 'Pago', 3 => 'Confirmación'];
    $routes = [1 => 'checkout.delivery', 2 => 'checkout.payment', 3 => 'checkout.confirm'];
@endphp

<x-layouts::app :title="$title">
    <div class="mx-auto max-w-7xl px-4 py-6 lg:py-10">
        <nav aria-label="Pasos de la compra">
            <ol class="flex items-center gap-2 text-sm">
                @foreach ($steps as $number => $label)
                    <li class="flex min-w-0 items-center gap-2">
                        @if ($number > 1)
                            <span class="h-px w-4 shrink-0 bg-brand-industrial/30 sm:w-8" aria-hidden="true"></span>
                        @endif

                        @php
                            $state = $number < $step ? 'done' : ($number === $step ? 'current' : 'next');
                        @endphp

                        <span @class([
                            'grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold',
                            'bg-brand-deep text-brand-electric' => $state !== 'next',
                            'bg-white text-brand-industrial ring-1 ring-brand-industrial/30' => $state === 'next',
                        ]) aria-hidden="true">
                            @if ($state === 'done')
                                <x-heroicon-s-check class="size-4" />
                            @else
                                {{ $number }}
                            @endif
                        </span>

                        @if ($state === 'done')
                            <a href="{{ route($routes[$number]) }}" class="focus-ring truncate rounded font-semibold underline-offset-4 hover:underline">{{ $label }}</a>
                        @else
                            <span @class(['truncate', 'font-bold' => $state === 'current', 'text-brand-industrial max-sm:sr-only' => $state === 'next']) @if ($state === 'current') aria-current="step" @endif>{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="mt-6 lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8">
            <div>
                <h1 class="text-2xl font-bold lg:text-3xl">{{ $title }}</h1>

                <div class="mt-6">
                    {{ $slot }}
                </div>
            </div>

            <aside aria-labelledby="resumen-compra" class="mt-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10 lg:sticky lg:top-6 lg:mt-0">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="resumen-compra" class="text-lg font-bold">Tu pedido</h2>
                    <a href="{{ route('cart.index') }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Editar carrito</a>
                </div>

                <ul class="mt-4 divide-y divide-brand-industrial/10 text-sm">
                    @foreach ($cart->lines as $line)
                        <li class="flex justify-between gap-4 py-2.5">
                            <span class="min-w-0">
                                <span class="line-clamp-2">{{ $line->product->name }}</span>
                                <span class="text-xs text-brand-industrial">{{ $line->product->unit->format($line->quantity) }} × {{ money($line->unitPrice) }}</span>
                            </span>
                            <span class="shrink-0 font-semibold">{{ money($line->subtotal) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-3 space-y-2 border-t border-brand-industrial/10 pt-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt>Subtotal</dt>
                        <dd class="font-semibold">{{ money($cart->subtotal) }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt>Envío</dt>
                        <dd>
                            @if ($shipping === 'unknown')
                                <span class="text-brand-industrial">Según tu dirección</span>
                            @elseif ($shipping === 'to_agree')
                                A coordinar
                            @elseif (bccomp($shipping, '0', 2) === 0)
                                Sin cargo
                            @else
                                {{ money($shipping) }}
                            @endif
                        </dd>
                    </div>

                    @if ($total !== null)
                        <div class="flex justify-between gap-4 border-t border-brand-industrial/10 pt-3 text-base">
                            <dt class="font-bold">Total{{ $shipping === 'to_agree' ? ' (sin envío)' : '' }}</dt>
                            <dd class="font-bold">{{ money($total) }}</dd>
                        </div>
                    @endif
                </dl>
            </aside>
        </div>
    </div>
</x-layouts::app>
