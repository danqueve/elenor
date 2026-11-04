<x-layout.header-link :href="route('cart.index')" label="Carrito">
    <span class="relative">
        <x-heroicon-o-shopping-cart class="size-6" />

        <span
            @class([
                'absolute -right-2.5 -top-2 grid min-w-5 place-items-center rounded-full px-1 text-[11px] font-bold leading-5',
                'bg-brand-electric text-brand-deep' => $this->count > 0,
                'bg-white/15 text-brand-neutral' => $this->count === 0,
            ])
            aria-hidden="true"
        >{{ $this->count }}</span>
    </span>

    <span class="sr-only">({{ trans_choice('{0} sin productos|{1} :count producto|[2,*] :count productos', $this->count) }})</span>
</x-layout.header-link>
