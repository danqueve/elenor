{{-- One-time notice after an action: "Guardamos tu teléfono.", "Cerraste sesión."… --}}
@if (session('status'))
    <div x-data="{ open: true }" x-show="open" class="mx-auto max-w-7xl px-4 pt-4">
        <div role="status" class="flex items-start justify-between gap-4 rounded-lg bg-white px-4 py-3 text-sm font-semibold shadow-sm ring-1 ring-brand-industrial/15">
            <p>{{ session('status') }}</p>

            <button
                type="button"
                class="focus-ring -m-1 rounded p-1 text-brand-industrial transition-colors hover:text-brand-deep"
                x-on:click="open = false"
                aria-label="Cerrar aviso"
            >
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>
    </div>
@endif
