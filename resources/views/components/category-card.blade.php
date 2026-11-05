@props(['category', 'total'])

@php($image = $category->imageUrl())

<a href="{{ route('categories.show', $category) }}" class="focus-ring group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-brand-industrial/10 transition hover:shadow-md">
    <div class="relative aspect-[16/10] bg-brand-deep">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="size-full object-cover">
        @else
            <span class="grid size-full place-items-center text-brand-electric" aria-hidden="true">
                <x-dynamic-component :component="'heroicon-o-'.$category->icon()" class="size-12 transition-transform group-hover:scale-110 sm:size-14" />
            </span>
        @endif
    </div>

    <div class="flex items-center justify-between gap-3 p-4">
        <div class="min-w-0">
            <h3 class="font-bold">{{ $category->name }}</h3>
            <p class="text-sm text-brand-industrial">{{ trans_choice('{0} Sin productos|{1} 1 producto|[2,*] :count productos', $total) }}</p>
        </div>

        <x-heroicon-o-arrow-right class="size-5 shrink-0 text-brand-industrial transition-transform group-hover:translate-x-1" aria-hidden="true" />
    </div>
</a>
