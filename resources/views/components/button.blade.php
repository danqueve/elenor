@props(['variant' => 'primary', 'href' => null])

{{--
    "primary" is the electric green button: use it once per screen.
    Everything else is "secondary".
--}}
@php
    $classes = [
        'focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors',
        match ($variant) {
            'primary' => 'bg-brand-electric text-brand-deep hover:bg-brand-electric-hover',
            'secondary' => 'border border-brand-industrial text-brand-deep hover:bg-brand-industrial hover:text-brand-neutral',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
