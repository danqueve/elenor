@php
    $contact = config('shop.contact');
    $whatsappUrl = $contact['whatsapp'] ? 'https://wa.me/'.preg_replace('/\D+/', '', $contact['whatsapp']) : null;
    $phoneUrl = $contact['phone'] ? 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']) : null;
    $linkClasses = 'focus-ring rounded transition-colors hover:text-brand-electric';
@endphp

{{-- Extra bottom padding on mobile so the fixed bottom menu never covers the footer. --}}
<footer class="bg-brand-deep pb-[calc(4rem+env(safe-area-inset-bottom))] text-brand-neutral/80 lg:pb-0">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-12">
        <div class="sm:col-span-2 lg:col-span-5">
            <x-layout.logo class="text-brand-neutral" />
            <p class="mt-4 max-w-sm text-sm leading-relaxed">
                Materiales eléctricos y herramientas para Tucumán y el NOA.
            </p>
        </div>

        <section class="lg:col-span-4" aria-labelledby="footer-contact">
            <h2 id="footer-contact" class="text-sm font-bold uppercase tracking-wider text-brand-neutral">Contacto</h2>

            <ul class="mt-4 space-y-3 text-sm">
                @if ($contact['address'])
                    <li class="flex gap-3">
                        <x-heroicon-o-map-pin class="mt-0.5 size-5 shrink-0 text-brand-neutral/60" />
                        <span>{{ $contact['address'] }}</span>
                    </li>
                @endif

                @if ($contact['hours'])
                    <li class="flex gap-3">
                        <x-heroicon-o-clock class="mt-0.5 size-5 shrink-0 text-brand-neutral/60" />
                        <span>{{ $contact['hours'] }}</span>
                    </li>
                @endif

                @if ($phoneUrl)
                    <li class="flex gap-3">
                        <x-heroicon-o-phone class="mt-0.5 size-5 shrink-0 text-brand-neutral/60" />
                        <a href="{{ $phoneUrl }}" class="{{ $linkClasses }}">{{ $contact['phone'] }}</a>
                    </li>
                @endif

                @if ($whatsappUrl)
                    <li class="flex gap-3">
                        <x-heroicon-o-chat-bubble-left-right class="mt-0.5 size-5 shrink-0 text-brand-neutral/60" />
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="{{ $linkClasses }}">Escribinos por WhatsApp</a>
                    </li>
                @endif

                @if ($contact['email'])
                    <li class="flex gap-3">
                        <x-heroicon-o-envelope class="mt-0.5 size-5 shrink-0 text-brand-neutral/60" />
                        <a href="mailto:{{ $contact['email'] }}" class="{{ $linkClasses }} break-all">{{ $contact['email'] }}</a>
                    </li>
                @endif
            </ul>
        </section>

        <nav class="lg:col-span-3" aria-labelledby="footer-shop">
            <h2 id="footer-shop" class="text-sm font-bold uppercase tracking-wider text-brand-neutral">Comprar</h2>

            <ul class="mt-4 space-y-3 text-sm">
                <li><a href="{{ route('catalog.index') }}" class="{{ $linkClasses }}">Catálogo</a></li>
                <li><a href="{{ route('quotes.index') }}" class="{{ $linkClasses }}">Presupuestos</a></li>
                <li><a href="{{ route('orders.index') }}" class="{{ $linkClasses }}">Mis pedidos</a></li>
                <li><a href="{{ route('favorites.index') }}" class="{{ $linkClasses }}">Favoritos</a></li>
            </ul>
        </nav>
    </div>

    <div class="border-t border-brand-industrial">
        <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-brand-neutral/60">
            &copy; {{ now()->year }} {{ config('app.name') }}. Todos los derechos reservados.
        </p>
    </div>
</footer>
