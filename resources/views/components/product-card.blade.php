@props(['product'])

@php
    $price = $product->priceDisplayFor(auth()->user());
    $image = $product->coverImageUrl();
@endphp

{{-- The whole card is one link (the name), stretched over the card. --}}
<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-brand-industrial/10 transition hover:shadow-md hover:ring-brand-industrial/25') }}>
    <div class="relative aspect-square bg-brand-neutral">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="size-full object-contain p-3 mix-blend-multiply">
        @else
            <span class="grid size-full place-items-center text-brand-industrial/20" aria-hidden="true">
                <x-heroicon-o-bolt class="size-16" />
            </span>
        @endif

        @if ($price->discountPercent)
            <span class="absolute left-2 top-2 rounded-md bg-brand-electric px-2 py-1 text-xs font-bold text-brand-deep">-{{ $price->discountPercent }}%</span>
        @endif

        @unless ($product->isInStock())
            <span class="absolute right-2 top-2 rounded-md bg-brand-industrial px-2 py-1 text-xs font-semibold text-brand-neutral">Sin stock</span>
        @endunless
    </div>

    <div class="flex flex-1 flex-col p-3 sm:p-4">
        @if ($product->brand)
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-industrial">{{ $product->brand->name }}</p>
        @endif

        <h3 class="mt-0.5 line-clamp-2 text-sm font-semibold leading-snug">
            <a href="{{ route('products.show', $product) }}" class="focus-ring rounded after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>

        <div class="mt-auto pt-3">
            @if ($price->wasPrice)
                <p class="text-xs text-brand-industrial line-through">{{ money($price->wasPrice) }}</p>
            @endif

            <p class="text-base font-bold sm:text-lg">
                {{ money($price->price) }}<span class="text-xs font-semibold text-brand-industrial">{{ $product->unit->priceSuffix() }}</span>
            </p>

            @if ($price->isProfessional)
                <p class="mt-1 inline-block rounded bg-brand-electric px-1.5 py-0.5 text-[11px] font-bold text-brand-deep">Precio profesional</p>
            @endif
        </div>
    </div>
</article>
