@props(['product'])

@php($images = $product->images->map(fn ($image) => $image->url())->values())

@if ($images->isEmpty())
    <div class="grid aspect-square place-items-center rounded-2xl bg-white text-brand-industrial/20 ring-1 ring-brand-industrial/10">
        <x-heroicon-o-photo class="size-24" aria-hidden="true" />
        <span class="sr-only">Este producto todavía no tiene fotos</span>
    </div>
@else
    <div x-data="{ active: 0, images: @js($images) }" class="space-y-3">
        <div class="aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-brand-industrial/10">
            <img src="{{ $images->first() }}" x-bind:src="images[active]" alt="{{ $product->name }}" class="size-full object-contain p-4">
        </div>

        @if ($images->count() > 1)
            <ul class="grid grid-cols-5 gap-2" aria-label="Fotos del producto">
                @foreach ($images as $url)
                    <li>
                        <button
                            type="button"
                            x-on:click="active = {{ $loop->index }}"
                            x-bind:aria-current="active === {{ $loop->index }}"
                            x-bind:class="active === {{ $loop->index }} ? 'ring-2 ring-brand-deep' : 'ring-1 ring-brand-industrial/15'"
                            class="focus-ring aspect-square w-full overflow-hidden rounded-lg bg-white"
                            aria-label="Ver la foto {{ $loop->iteration }}"
                        >
                            <img src="{{ $url }}" alt="" loading="lazy" class="size-full object-contain p-1">
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
