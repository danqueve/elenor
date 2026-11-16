@php
    $awaitingTransfer = $order->payment_method === App\Enums\PaymentMethod::BankTransfer
        && $order->payment_status === App\Enums\PaymentStatus::Pending
        && $order->status !== App\Enums\OrderStatus::Cancelled;
    $whatsapp = App\Support\WhatsApp::url("¡Hola! Te escribo por mi pedido {$order->number}.");
@endphp

<x-layouts::app :title="'Pedido '.$order->number">
    <section class="mx-auto max-w-5xl px-4 py-6 lg:py-10">
        <x-breadcrumb :items="[['label' => 'Mis pedidos', 'url' => route('orders.index')], ['label' => $order->number, 'url' => null]]" />

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold lg:text-3xl">Pedido {{ $order->number }}</h1>
                <p class="mt-1 text-sm text-brand-industrial">Hecho el {{ $order->created_at->translatedFormat('j \d\e F \d\e Y, H:i') }} h</p>
            </div>

            <x-order-status-badge :status="$order->status" class="text-sm" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <div class="space-y-6">
                @if ($awaitingTransfer && $bank !== [])
                    <section aria-labelledby="transferencia" class="rounded-2xl bg-brand-deep p-5 text-brand-neutral">
                        <h2 id="transferencia" class="font-bold text-white">Datos para la transferencia</h2>
                        <p class="mt-1 text-sm">Transferí <span class="font-bold text-brand-electric">{{ money($order->total) }}</span>{{ $order->shipping_cost === null ? ' (el envío se suma cuando lo coordinemos)' : '' }} y mandanos el comprobante con el número de pedido.</p>

                        <dl class="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                            @foreach (['holder' => 'Titular', 'bank' => 'Banco', 'cbu' => 'CBU', 'alias' => 'Alias', 'cuit' => 'CUIT'] as $key => $label)
                                @isset($bank[$key])
                                    <div>
                                        <dt class="text-brand-neutral/70">{{ $label }}</dt>
                                        <dd class="font-semibold text-white">{{ $bank[$key] }}</dd>
                                    </div>
                                @endisset
                            @endforeach
                        </dl>

                        @if ($whatsapp)
                            <p class="mt-4">
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="focus-ring inline-flex items-center gap-2 rounded text-sm font-semibold text-brand-electric underline underline-offset-4">
                                    <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" />
                                    Mandar el comprobante por WhatsApp
                                </a>
                            </p>
                        @endif
                    </section>
                @endif

                <section aria-labelledby="seguimiento" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
                    <h2 id="seguimiento" class="font-bold">Seguimiento</h2>

                    <ol class="mt-4">
                        @foreach ($timeline as $step)
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @unless ($loop->last)
                                    <span @class(['absolute left-[11px] top-6 h-[calc(100%-1.5rem)] w-0.5', 'bg-brand-deep' => $step['done'], 'bg-brand-industrial/20' => ! $step['done']]) aria-hidden="true"></span>
                                @endunless

                                <span @class([
                                    'relative grid size-6 shrink-0 place-items-center rounded-full',
                                    'bg-brand-deep text-brand-electric' => $step['done'],
                                    'bg-white ring-2 ring-brand-industrial/25' => ! $step['done'],
                                ]) aria-hidden="true">
                                    @if ($step['done'])
                                        <x-heroicon-s-check class="size-3.5" />
                                    @endif
                                </span>

                                <div class="min-w-0 text-sm" @if ($step['current']) aria-current="step" @endif>
                                    <p @class(['font-bold' => $step['current'], 'font-semibold' => $step['done'] && ! $step['current'], 'text-brand-industrial' => ! $step['done']])>
                                        {{ $step['status']->label() }}
                                        <span class="sr-only">{{ $step['done'] ? '(hecho)' : '(pendiente)' }}</span>
                                    </p>
                                    @if ($step['at'])
                                        <p class="text-brand-industrial">{{ $step['at']->translatedFormat('j \d\e F, H:i') }} h</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section aria-labelledby="productos" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
                    <h2 id="productos" class="font-bold">Productos</h2>

                    <ul class="mt-3 divide-y divide-brand-industrial/10 text-sm">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    @if ($item->product?->is_active)
                                        <a href="{{ route('products.show', $item->product) }}" class="focus-ring rounded font-semibold hover:underline">{{ $item->product_name }}</a>
                                    @else
                                        <span class="font-semibold">{{ $item->product_name }}</span>
                                    @endif
                                    <p class="text-xs text-brand-industrial">SKU {{ $item->product_sku }} · {{ $item->product_unit->format($item->quantity) }} × {{ money($item->unit_price) }}</p>
                                </div>

                                <span class="shrink-0 font-semibold">{{ money($item->subtotal) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside class="space-y-4">
                <section aria-labelledby="totales" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
                    <h2 id="totales" class="font-bold">Resumen</h2>

                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd class="font-semibold">{{ money($order->subtotal) }}</dd></div>
                        <div class="flex justify-between gap-4">
                            <dt>Envío</dt>
                            <dd>{{ $order->shipping_cost === null ? 'A coordinar' : (bccomp($order->shipping_cost, '0', 2) === 0 ? 'Sin cargo' : money($order->shipping_cost)) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-brand-industrial/10 pt-2 text-base">
                            <dt class="font-bold">Total{{ $order->shipping_cost === null ? ' (sin envío)' : '' }}</dt>
                            <dd class="font-bold">{{ money($order->total) }}</dd>
                        </div>
                    </dl>

                    <dl class="mt-4 space-y-3 border-t border-brand-industrial/10 pt-4 text-sm">
                        <div>
                            <dt class="text-brand-industrial">{{ $order->shipping_method->label() }}</dt>
                            <dd>
                                @if ($order->shipping_street)
                                    {{ $order->shipping_street }} {{ $order->shipping_number }}, {{ $order->shipping_city }}, {{ $order->shipping_province }} ({{ $order->shipping_postal_code }})
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-brand-industrial">Pago</dt>
                            <dd>{{ $order->payment_method->label() }} · {{ $order->payment_status->label() }}</dd>
                        </div>
                        @if (filled($order->notes))
                            <div>
                                <dt class="text-brand-industrial">Comentarios</dt>
                                <dd>{{ $order->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <form method="POST" action="{{ route('orders.reorder', $order) }}">
                    @csrf

                    <x-button type="submit" class="w-full">
                        <x-heroicon-o-arrow-path class="size-5" aria-hidden="true" />
                        Repetir pedido
                    </x-button>
                </form>
            </aside>
        </div>
    </section>
</x-layouts::app>
