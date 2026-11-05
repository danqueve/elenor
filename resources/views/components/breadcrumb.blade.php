@props(['items'])

{{-- $items: [['label' => 'Inicio', 'url' => '/'], …]. The last one is the current page. --}}
<nav aria-label="Ruta de navegación" class="text-sm text-brand-industrial">
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
        @foreach ($items as $item)
            <li class="flex min-w-0 items-center gap-1.5">
                @unless ($loop->first)
                    <x-heroicon-o-chevron-right class="size-3.5 shrink-0 text-brand-industrial/50" aria-hidden="true" />
                @endunless

                @if ($item['url'] && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="focus-ring rounded underline-offset-4 hover:text-brand-deep hover:underline">{{ $item['label'] }}</a>
                @else
                    <span class="truncate font-semibold text-brand-deep" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
