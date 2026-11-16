@php
    $unit = $this->product->unit;
@endphp

<form wire:submit="add" class="space-y-3">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label for="add-quantity" class="block text-sm font-semibold">
                Cantidad {{ $unit === App\Enums\ProductUnit::Unit ? '' : '('.mb_strtolower($unit->label()).'s)' }}
            </label>

            <div class="mt-2 flex h-11 items-stretch overflow-hidden rounded-lg bg-white ring-1 ring-brand-industrial/30">
                <button type="button" wire:click="decrement" class="focus-ring grid w-11 place-items-center text-brand-industrial hover:bg-brand-neutral" aria-label="Restar">
                    <x-heroicon-o-minus class="size-4" />
                </button>

                <input
                    id="add-quantity"
                    type="text"
                    inputmode="decimal"
                    wire:model="quantity"
                    @error('quantity') aria-invalid="true" aria-describedby="add-quantity-error" @enderror
                    class="focus-ring w-20 border-x border-brand-industrial/15 text-center text-base font-semibold lg:text-sm"
                >

                <button type="button" wire:click="increment" class="focus-ring grid w-11 place-items-center text-brand-industrial hover:bg-brand-neutral" aria-label="Sumar">
                    <x-heroicon-o-plus class="size-4" />
                </button>
            </div>
        </div>

        <x-button type="submit" class="flex-1 sm:flex-none" wire:loading.attr="disabled" wire:target="add">
            <x-heroicon-o-shopping-cart class="size-5" aria-hidden="true" />
            Agregar al carrito
        </x-button>
    </div>

    @if ($unit === App\Enums\ProductUnit::Meter)
        <p class="text-sm text-brand-industrial">Se corta a medida: de a medio metro.</p>
    @endif

    @error('quantity')
        <p id="add-quantity-error" class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
    @enderror

    @if ($added)
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-white px-4 py-3 text-sm font-semibold ring-1 ring-brand-industrial/15" role="status">
            <x-heroicon-s-check-circle class="size-5 shrink-0" aria-hidden="true" />
            <span>Listo: tenés {{ $unit->format($added) }} en el carrito.</span>
            <a href="{{ route('cart.index') }}" class="focus-ring rounded underline underline-offset-4 hover:text-brand-industrial">Ver carrito</a>
        </p>
    @endif
</form>
