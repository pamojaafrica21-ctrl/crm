<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-favicon />
        <title>{{ config('app.name', 'Core CRM') }}</title>
        <meta name="robots" content="noindex, follow">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-stone-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">
            <aside class="relative isolate overflow-hidden bg-[#143529] text-white px-8 py-10 lg:px-14 lg:py-16 flex flex-col justify-between min-h-[42vh] lg:min-h-screen">
                <div class="pointer-events-none absolute inset-0 -z-10 animate-drift"
                     style="background:
                        radial-gradient(ellipse 80% 60% at 20% 20%, rgba(120, 170, 140, 0.35), transparent 55%),
                        radial-gradient(ellipse 70% 50% at 90% 80%, rgba(50, 90, 70, 0.55), transparent 50%),
                        linear-gradient(165deg, #1a4535 0%, #0f2f24 45%, #1c3d32 100%);">
                </div>
                <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.12]"
                     style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"1\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                </div>

                <div class="animate-fade-in">
                    <div class="inline-flex items-center gap-3">
                        <img src="{{ asset('favicon-32x32.png') }}" alt="{{ config('app.name', 'Core CRM') }}" width="44" height="44" class="w-11 h-11 rounded-xl ring-1 ring-white/25">
                        <span class="text-sm font-medium text-emerald-100/80 tracking-wide">Staff portal</span>
                    </div>
                </div>

                <div class="mt-12 lg:mt-0 max-w-lg animate-fade-up" style="animation-delay: 120ms">
                    <p class="text-emerald-100/70 text-sm uppercase tracking-[0.2em] mb-4">{{ config('app.name', 'Core CRM') }}</p>
                    <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-semibold leading-[1.05] text-white">
                        Welcome back.
                    </h1>
                    <p class="mt-5 text-base sm:text-lg text-emerald-50/75 leading-relaxed max-w-md">
                        Sign in to manage guests, quotes, invoices, and day-to-day operations for your organisation.
                    </p>
                </div>

                <div class="mt-10 lg:mt-0 text-sm text-emerald-100/55 animate-fade-in" style="animation-delay: 280ms">
                    Secure staff access · {{ config('app.name', 'Core CRM') }}
                </div>
            </aside>

            <main class="relative flex items-center justify-center px-6 py-12 sm:px-10 bg-[#f3efe6]">
                <div class="pointer-events-none absolute inset-0 opacity-40"
                     style="background:
                        radial-gradient(ellipse 50% 40% at 100% 0%, rgba(20, 53, 41, 0.08), transparent 60%),
                        radial-gradient(ellipse 40% 30% at 0% 100%, rgba(120, 100, 70, 0.08), transparent 55%);">
                </div>

                <div class="relative w-full max-w-md animate-fade-up" style="animation-delay: 180ms">
                    <div class="lg:hidden mb-8">
                        <div class="font-display text-2xl font-semibold text-[#143529]">{{ config('app.name', 'Core CRM') }}</div>
                        <div class="text-sm text-stone-500 mt-1">Staff portal</div>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
