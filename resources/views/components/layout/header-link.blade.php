@props(['href', 'label'])

{{-- Icon link for the header. The label is only visible on desktop. --}}
<a
    href="{{ $href }}"
    {{ $attributes->class('focus-ring inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-lg px-2 text-brand-neutral transition-colors hover:bg-white/10 lg:px-3') }}
>
    {{ $slot }}
    <span class="sr-only lg:not-sr-only lg:text-sm lg:font-semibold">{{ $label }}</span>
</a>
