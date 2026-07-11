<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($portalProperty->name ?? config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-portal antialiased text-stone-800 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-stone-100 via-amber-50/40 to-stone-200">
    <div class="min-h-screen flex flex-col justify-center px-4 py-12">
        <div class="mx-auto mb-8 text-center">
            <a href="{{ route('portal.home') }}" wire:navigate class="font-display text-4xl text-stone-900">{{ $portalProperty->name ?? __('portal.brand') }}</a>
        </div>
        <div class="mx-auto w-full max-w-md rounded-3xl border border-stone-200/80 bg-white/90 p-8 shadow-xl shadow-stone-900/5 backdrop-blur">
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>
</html>
