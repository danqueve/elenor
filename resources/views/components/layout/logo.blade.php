{{-- Placeholder logo: replace with the final brand asset. --}}
<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    <span class="grid size-9 place-items-center rounded-lg bg-brand-electric text-brand-deep" aria-hidden="true">
        <x-heroicon-s-bolt class="size-5" />
    </span>
    <span class="text-xl font-bold uppercase tracking-wider">{{ config('app.name') }}</span>
</span>
