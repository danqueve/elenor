@php
    $user = auth()->user();
    $price = $product->priceDisplayFor($user);
    $inStock = $product->isInStock();
    $url = route('products.show', $product);
    $cover = $product->coverImageUrl();
    $whatsapp = App\Support\WhatsApp::url("¡Hola! Quería consultar por este producto: {$product->name} (SKU {$product->sku}).\n{$url}");

    $breadcrumb = [['label' => 'Inicio', 'url' => route('home')], ['label' => 'Catálogo', 'url' => route('catalog.index')]];

    foreach ($path as $category) {
        $breadcrumb[] = ['label' => $category->name, 'url' => route('categories.show', $category)];
    }

    $breadcrumb[] = ['label' => $product->name, 'url' => null];

    // The technical sheet: the fixed data first, then the attributes the store entered.
    $sheet = array_filter([
        'Marca' => $product->brand?->name,
        'SKU' => $product->sku,
        'Código de fabricante' => $product->manufacturer_code,
        'Se vende por' => mb_strtolower($product->unit->label()),
    ]) + $product->specs->pluck('value', 'name')->all();

    // What search engines and link previews read. The price is the public one.
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'mpn' => $product->manufacturer_code,
        'description' => $product->description,
        'image' => $cover ? url($cover) : null,
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
        'offers' => [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => config('shop.currency.code'),
            'price' => $product->list_price,
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ]);
@endphp

<x-layouts::app :title="$product->name" :description="str($product->description ?? $product->name)->limit(160)->toString()">
    <x-slot:head>
        <link rel="canonical" href="{{ $url }}">
        <meta property="og:type" content="product">
        <meta property="og:title" content="{{ $product->name }}">
        <meta property="og:description" content="{{ str($product->description ?? $product->name)->limit(200) }}">
        <meta property="og:url" content="{{ $url }}">
        @if ($cover)
            <meta property="og:image" content="{{ url($cover) }}">
        @endif
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-7xl px-4 py-6 lg:py-10">
        <x-breadcrumb :items="$breadcrumb" />

        <div class="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-12">
            <x-product-gallery :product="$product" />

            <div>
                @if ($product->brand)
                    <p class="text-sm font-semibold uppercase tracking-wider text-brand-industrial">{{ $product->brand->name }}</p>
                @endif

                <h1 class="mt-1 text-2xl font-bold lg:text-3xl">{{ $product->name }}</h1>

                <p class="mt-2 text-sm text-brand-industrial">
                    SKU <span class="font-semibold text-brand-deep">{{ $product->sku }}</span>
                    @if ($product->manufacturer_code)
                        · Código de fabricante <span class="font-semibold text-brand-deep">{{ $product->manufacturer_code }}</span>
                    @endif
                </p>

                <div class="mt-6">
                    @if ($price->wasPrice)
                        <p class="text-sm text-brand-industrial line-through">{{ money($price->wasPrice) }}</p>
                    @endif

                    <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="text-3xl font-bold">{{ money($price->price) }}</span>

                        @if ($price->discountPercent)
                            <span class="rounded-md bg-brand-electric px-2 py-1 text-xs font-bold text-brand-deep">-{{ $price->discountPercent }}%</span>
                        @endif

                        @if ($price->isProfessional)
                            <span class="rounded-md bg-brand-electric px-2 py-1 text-xs font-bold text-brand-deep">Precio profesional</span>
                        @endif
                    </p>

                    <p class="mt-1 text-sm text-brand-industrial">Precio por {{ mb_strtolower($product->unit->label()) }}</p>
                </div>

                <p class="mt-5 flex items-center gap-2 text-sm font-semibold">
                    @if ($inStock)
                        <x-heroicon-s-check-circle class="size-5 shrink-0 text-brand-deep" aria-hidden="true" />
                        Disponible: {{ $product->unit->format($product->stock) }} en stock
                    @else
                        <x-heroicon-o-exclamation-triangle class="size-5 shrink-0 text-red-700" aria-hidden="true" />
                        <span class="text-red-700">Sin stock por el momento</span>
                    @endif
                </p>

                @if ($whatsapp)
                    <div class="mt-6">
                        <x-button :href="$whatsapp" target="_blank" rel="noopener" class="w-full sm:w-auto">
                            <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" />
                            Consultar por WhatsApp
                        </x-button>
                    </div>
                @endif

                @if (filled($product->description))
                    <section class="mt-8" aria-labelledby="descripcion">
                        <h2 id="descripcion" class="text-lg font-bold">Descripción</h2>
                        <p class="mt-2 leading-relaxed text-brand-industrial">{{ $product->description }}</p>
                    </section>
                @endif

                <section class="mt-8" aria-labelledby="ficha-tecnica">
                    <h2 id="ficha-tecnica" class="text-lg font-bold">Ficha técnica</h2>

                    <dl class="mt-3 divide-y divide-brand-industrial/10 overflow-hidden rounded-xl bg-white text-sm ring-1 ring-brand-industrial/10">
                        @foreach ($sheet as $label => $value)
                            <div class="grid grid-cols-2 gap-4 px-4 py-3">
                                <dt class="text-brand-industrial">{{ $label }}</dt>
                                <dd class="font-semibold">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14" aria-labelledby="relacionados">
                <h2 id="relacionados" class="text-xl font-bold lg:text-2xl">Productos relacionados</h2>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
