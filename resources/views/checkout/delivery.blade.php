@php
    $selectedMethod = old('shipping_method', $values['shipping_method'] ?? $methods[0]->value);
    $selectedProvince = old('province', $values['province'] ?? 'Tucumán');
    $hasAgreedCosts = $costs->contains(null);
@endphp

<x-checkout.layout :step="1" title="¿Dónde te lo enviamos?" :cart="$cart">
    <form method="POST" action="{{ route('checkout.delivery.store') }}" class="space-y-6" x-data="{ method: @js($selectedMethod) }">
        @csrf

        @if (count($methods) > 1)
            <fieldset>
                <legend class="text-sm font-semibold">Forma de entrega</legend>

                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach ($methods as $method)
                        <x-radio-card name="shipping_method" :value="$method->value" :checked="$selectedMethod === $method->value" :label="$method->label()" x-model="method" />
                    @endforeach
                </div>
            </fieldset>
        @else
            <input type="hidden" name="shipping_method" value="{{ $methods[0]->value }}">
        @endif

        <div x-show="method === 'delivery'" class="space-y-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-industrial/10">
            <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_9rem]">
                <x-input name="street" label="Calle" autocomplete="address-line1" :value="old('street', $values['street'] ?? '')" />
                <x-input name="number" label="Altura" inputmode="numeric" :value="old('number', $values['number'] ?? '')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="city" label="Localidad" autocomplete="address-level2" :value="old('city', $values['city'] ?? '')" />

                <div>
                    <label for="province" class="block text-sm font-semibold">Provincia</label>

                    <select
                        id="province"
                        name="province"
                        autocomplete="address-level1"
                        @error('province') aria-invalid="true" aria-describedby="province-error" @enderror
                        @class([
                            'focus-ring mt-2 block h-11 w-full rounded-lg bg-white px-3 text-base ring-1 lg:text-sm',
                            'ring-brand-industrial/30' => ! $errors->has('province'),
                            'ring-2 ring-red-700' => $errors->has('province'),
                        ])
                    >
                        @foreach ($provinces as $province)
                            <option value="{{ $province }}" @selected($selectedProvince === $province)>
                                {{ $province }}{{ $costs[$province] === null ? '' : ' · envío '.(bccomp($costs[$province], '0', 2) === 0 ? 'sin cargo' : money($costs[$province])) }}
                            </option>
                        @endforeach
                    </select>

                    @error('province')
                        <p id="province-error" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-[9rem_minmax(0,1fr)]">
                <x-input name="postal_code" label="Código postal" autocomplete="postal-code" :value="old('postal_code', $values['postal_code'] ?? '')" />
                <x-input name="reference" label="Referencia (opcional)" placeholder="Entre calles, color del portón…" :value="old('reference', $values['reference'] ?? '')" />
            </div>

            @if ($hasAgreedCosts)
                <p class="flex items-start gap-2 text-sm text-brand-industrial">
                    <x-heroicon-o-information-circle class="size-5 shrink-0" aria-hidden="true" />
                    El costo del envío depende de tu zona. Si no figura, te lo confirmamos por WhatsApp antes de despachar, y recién ahí lo pagás.
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit">Continuar al pago</x-button>
            <x-button variant="secondary" :href="route('cart.index')">Volver al carrito</x-button>
        </div>
    </form>
</x-checkout.layout>
