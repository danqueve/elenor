@props(['user'])

{{--
    The Google profile picture over the initials: if the picture is missing or
    fails to load, the initials underneath show instead.
--}}
<span
    {{ $attributes->class('relative inline-grid size-8 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-industrial text-xs font-bold text-brand-neutral') }}
    @if ($user->avatar) x-data="{ failed: false }" @endif
>
    <span aria-hidden="true">{{ $user->initials() }}</span>

    @if ($user->avatar)
        <img
            src="{{ $user->avatar }}"
            alt=""
            referrerpolicy="no-referrer"
            class="absolute inset-0 size-full object-cover"
            x-show="! failed"
            x-init="failed = $el.complete && $el.naturalWidth === 0"
            x-on:error="failed = true"
        >
    @endif
</span>
