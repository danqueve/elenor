@php
    $address = $data->address;
@endphp

<x-checkout.layout :step="3" title="Revisá y confirmá" :cart="$cart" :shipping="$shippingCost ?? 'to_agree'" :total="$total">
    <div class="space-y-4">
        <section aria-labelledby="entrega" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
            <div class="flex items-center justify-between gap-4">
                <h2 id="entrega" class="font-bold">{{ $data->shippingMethod->label() }}</h2>
                <a href="{{ route('checkout.delivery') }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Cambiar</a>
            </div>

            @if ($address)
                <p class="mt-2 text-sm">
                    {{ $address['street'] }} {{ $address['number'] }}, {{ $address['city'] }}, {{ $address['province'] }} ({{ $address['postal_code'] }})
                    @if (filled($address['reference']))
                        <br><span class="text-brand-industrial">{{ $address['reference'] }}</span>
                    @endif
                </p>
            @endif

            @if ($shippingCost === null)
                <p class="mt-3 flex items-start gap-2 text-sm text-brand-industrial">
                    <x-heroicon-o-information-circle class="size-5 shrink-0" aria-hidden="true" />
                    El envío a tu zona se coordina: te confirmamos el costo por WhatsApp antes de despachar.
                </p>
            @endif
        </section>

        <section aria-labelledby="pago" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
            <div class="flex items-center justify-between gap-4">
                <h2 id="pago" class="font-bold">{{ $data->paymentMethod->label() }}</h2>
                <a href="{{ route('checkout.payment') }}" class="focus-ring rounded text-sm font-semibold underline underline-offset-4 hover:text-brand-industrial">Cambiar</a>
            </div>

            <p class="mt-2 text-sm text-brand-industrial">{{ $data->paymentMethod->description() }}</p>

            @if (filled($data->notes))
                <p class="mt-3 text-sm"><span class="font-semibold">Comentarios:</span> {{ $data->notes }}</p>
            @endif
        </section>

        @auth
            <form method="POST" action="{{ route('checkout.place') }}" class="pt-2">
                @csrf

                <x-button type="submit" class="w-full sm:w-auto">
                    <x-heroicon-o-check-circle class="size-5" aria-hidden="true" />
                    Confirmar pedido
                </x-button>

                <p class="mt-3 text-sm text-brand-industrial">Al confirmar, reservamos el stock y te mandamos el detalle a {{ auth()->user()->email }}.</p>
            </form>
        @else
            <div class="rounded-2xl bg-brand-deep p-5 text-brand-neutral">
                <h2 class="font-bold text-white">Último paso: ingresá para confirmar</h2>
                <p class="mt-1 text-sm">Usamos tu cuenta de Google para guardar el pedido y avisarte cómo avanza. Tu carrito no se pierde.</p>

                <div class="mt-4">
                    <x-button :href="route('auth.google.redirect')">Ingresar con Google y confirmar</x-button>
                </div>
            </div>
        @endauth
    </div>
</x-checkout.layout>
