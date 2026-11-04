@props([
    'title' => null,
    'description' => 'Materiales eléctricos y herramientas para Tucumán y el NOA: cables, protección eléctrica, mecanismos, iluminación LED, canalización y más.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1F17">
    <meta name="description" content="{{ $description }}">

    <title>{{ $title ? $title.' | '.config('app.name') : config('app.name').' | Materiales eléctricos y herramientas' }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col bg-brand-neutral font-sans text-brand-deep antialiased">
    <a
        href="#contenido"
        class="focus-ring sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold"
    >
        Saltar al contenido
    </a>

    <x-layout.header />
    <x-layout.category-bar />

    <main id="contenido" class="flex-1">
        {{ $slot }}
    </main>

    <x-layout.footer />
    <x-layout.mobile-nav />

    @livewireScriptConfig
</body>
</html>
