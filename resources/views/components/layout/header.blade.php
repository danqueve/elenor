<header
    x-data="{ scrolled: false }"
    x-on:scroll.window.passive="scrolled = window.scrollY > 8"
    x-bind:class="{ 'shadow-lg shadow-black/30': scrolled }"
    class="sticky top-0 z-40 bg-brand-deep text-brand-neutral transition-shadow duration-200"
>
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-3 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))] lg:flex-nowrap lg:gap-x-8 lg:py-4">
        <a
            href="{{ route('home') }}"
            class="focus-ring rounded-lg"
            aria-label="{{ config('app.name') }}: ir al inicio"
        >
            <x-layout.logo />
        </a>

        {{-- Search with suggestions: second row on mobile, centered on desktop. --}}
        <livewire:search-box />

        <nav aria-label="Accesos rápidos" class="ml-auto flex items-center gap-1">
            {{-- On mobile the account entry is the "Cuenta" tab of the bottom menu. --}}
            <div class="hidden lg:block">
                @auth
                    <x-layout.header-link :href="route('account')" :label="auth()->user()->firstName()">
                        <x-user-avatar :user="auth()->user()" class="size-7" />
                    </x-layout.header-link>
                @else
                    <x-layout.header-link :href="route('login')" label="Ingresar">
                        <x-heroicon-o-user class="size-6" />
                    </x-layout.header-link>
                @endauth
            </div>

            <x-layout.header-link :href="route('favorites.index')" label="Favoritos">
                <x-heroicon-o-heart class="size-6" />
            </x-layout.header-link>

            <livewire:cart-counter />
        </nav>
    </div>
</header>
