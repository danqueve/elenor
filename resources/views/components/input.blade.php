@props(['name', 'label', 'hint' => null, 'type' => 'text', 'value' => null])

@php
    $describedBy = collect([$hint ? $name.'-hint' : null, $errors->has($name) ? $name.'-error' : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($errors->has($name)) aria-invalid="true" @endif
        {{ $attributes->class([
            'focus-ring mt-2 block h-11 w-full rounded-lg bg-white px-4 text-base text-brand-deep ring-1 lg:text-sm',
            'ring-brand-industrial/30' => ! $errors->has($name),
            'ring-2 ring-red-700' => $errors->has($name),
        ]) }}
    >

    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-2 text-sm text-brand-industrial">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="mt-2 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>
