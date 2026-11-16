@php
    $selected = old('payment_method', $values['payment_method'] ?? $methods[0]->value);
@endphp

<x-checkout.layout :step="2" title="¿Cómo pagás?" :cart="$cart">
    <form method="POST" action="{{ route('checkout.payment.store') }}" class="space-y-6">
        @csrf

        <fieldset>
            <legend class="text-sm font-semibold">Forma de pago</legend>

            <div class="mt-2 grid gap-3">
                @foreach ($methods as $method)
                    <x-radio-card name="payment_method" :value="$method->value" :checked="$selected === $method->value" :label="$method->label()" :description="$method->description()" />
                @endforeach
            </div>

            @error('payment_method')
                <p class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
            @enderror

            <p class="mt-3 text-sm text-brand-industrial">Muy pronto: Mercado Pago, con tarjeta y en cuotas.</p>
        </fieldset>

        <div>
            <label for="notes" class="block text-sm font-semibold">Comentarios (opcional)</label>

            <textarea
                id="notes"
                name="notes"
                rows="3"
                maxlength="1000"
                placeholder="Horarios para recibir, si necesitás factura A…"
                @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror
                class="focus-ring mt-2 block w-full rounded-lg bg-white px-4 py-3 text-base ring-1 ring-brand-industrial/30 lg:text-sm"
            >{{ old('notes', $values['notes'] ?? '') }}</textarea>

            @error('notes')
                <p id="notes-error" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit">Revisar el pedido</x-button>
            <x-button variant="secondary" :href="route('checkout.delivery')">Volver</x-button>
        </div>
    </form>
</x-checkout.layout>
