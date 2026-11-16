@props(['name', 'value', 'checked' => false, 'label', 'description' => null])

{{-- A big clickable option (shipping or payment method). --}}
<label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white p-4 ring-1 ring-brand-industrial/15 has-checked:ring-2 has-checked:ring-brand-deep">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked) class="focus-ring mt-1 size-4 accent-brand-deep" {{ $attributes }}>

    <span>
        <span class="block font-semibold">{{ $label }}</span>
        @if ($description)
            <span class="mt-0.5 block text-sm text-brand-industrial">{{ $description }}</span>
        @endif
    </span>
</label>
