@php
    // The page links scroll back to the top of the results (pass ['scrollTo' => '#id'] to links()).
    $scrollTo ??= 'body';

    $scrollIntoView = $scrollTo === false
        ? ''
        : "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView({ behavior: 'smooth' })";

    $page = $paginator->getPageName();
    $button = 'focus-ring inline-flex h-10 min-w-10 items-center justify-center rounded-lg bg-white px-3 text-sm font-semibold ring-1 ring-brand-industrial/20 transition-colors hover:bg-brand-deep hover:text-brand-neutral';
    $disabled = 'inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-semibold text-brand-industrial/40 ring-1 ring-brand-industrial/10';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Páginas" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
            <p class="text-sm text-brand-industrial">
                Mostrando <span class="font-semibold text-brand-deep">{{ $paginator->firstItem() }}</span>
                a <span class="font-semibold text-brand-deep">{{ $paginator->lastItem() }}</span>
                de <span class="font-semibold text-brand-deep">{{ $paginator->total() }}</span>
            </p>

            <div class="flex items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    {{-- The labels come with HTML entities (&laquo;), as in Laravel's own pagination views. --}}
                    <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.previous') !!}</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $page }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $button }}">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif

                {{-- Page numbers: only from the "sm" breakpoint up, on phones it is previous / next. --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="hidden px-1 text-brand-industrial sm:inline" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $number => $url)
                            <span class="hidden sm:inline" wire:key="paginator-{{ $page }}-page{{ $number }}">
                                @if ($number == $paginator->currentPage())
                                    <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-lg bg-brand-deep px-3 text-sm font-semibold text-brand-neutral" aria-current="page">{{ $number }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $number }}, '{{ $page }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $button }}" aria-label="Ir a la página {{ $number }}">
                                        {{ $number }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                <span class="px-2 text-sm font-semibold sm:hidden" aria-hidden="true">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $page }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $button }}">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.next') !!}</span>
                @endif
            </div>
        </nav>
    @endif
</div>
