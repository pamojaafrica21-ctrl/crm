<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $portalProperty->name ?? config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600,700|outfit:300,400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-portal antialiased text-stone-800 bg-stone-50">
    <div class="min-h-screen flex flex-col">
        <header class="absolute inset-x-0 top-0 z-40" x-data="{ open: false }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <a href="{{ route('portal.home') }}" wire:navigate class="flex items-center gap-3 text-white drop-shadow">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full border border-white/40 bg-white/10 text-sm font-semibold tracking-widest backdrop-blur">
                            {{ strtoupper(substr($portalProperty->code ?? 'H', 0, 2)) }}
                        </span>
                        <span class="font-display text-2xl tracking-tight">{{ $portalProperty->name ?? __('portal.brand') }}</span>
                    </a>

                    <nav class="hidden items-center gap-8 text-sm font-medium text-white/90 lg:flex">
                        @if(Route::has('portal.rooms.index'))
                            <a href="{{ route('portal.rooms.index') }}" wire:navigate class="hover:text-white transition">{{ __('portal.nav.rooms') }}</a>
                        @endif
                        @if(Route::has('portal.restaurant.menu'))
                            <a href="{{ route('portal.restaurant.menu') }}" wire:navigate class="hover:text-white transition">{{ __('portal.nav.restaurant') }}</a>
                        @endif
                        @if(Route::has('portal.promotions.index'))
                            <a href="{{ route('portal.promotions.index') }}" wire:navigate class="hover:text-white transition">{{ __('portal.nav.promotions') }}</a>
                        @endif
                        @if(Route::has('portal.faq.index'))
                            <a href="{{ route('portal.faq.index') }}" wire:navigate class="hover:text-white transition">{{ __('portal.nav.faq') }}</a>
                        @endif
                        @if(Route::has('portal.contact.index'))
                            <a href="{{ route('portal.contact.index') }}" wire:navigate class="hover:text-white transition">{{ __('portal.nav.contact') }}</a>
                        @endif
                    </nav>

                    <div class="hidden items-center gap-3 lg:flex">
                        @auth('guest')
                            <a href="{{ route('portal.dashboard') }}" wire:navigate class="text-sm font-medium text-white/90 hover:text-white">{{ __('portal.nav.dashboard') }}</a>
                            <form method="POST" action="{{ route('guest.logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-white/70 hover:text-white">{{ __('portal.nav.logout') }}</button>
                            </form>
                        @else
                            <a href="{{ route('guest.login') }}" wire:navigate class="text-sm font-medium text-white/90 hover:text-white">{{ __('portal.nav.login') }}</a>
                            @if(Route::has('portal.rooms.index'))
                                <a href="{{ route('portal.rooms.index') }}" wire:navigate class="rounded-full bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-950 shadow-lg shadow-amber-900/20 transition hover:bg-amber-400">
                                    {{ __('portal.nav.book_now') }}
                                </a>
                            @else
                                <a href="{{ route('guest.register') }}" wire:navigate class="rounded-full bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-950 shadow-lg shadow-amber-900/20 transition hover:bg-amber-400">
                                    {{ __('portal.nav.register') }}
                                </a>
                            @endif
                        @endauth
                    </div>

                    <button type="button" class="lg:hidden text-white" @click="open = !open" aria-label="Menu">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                </div>
            </div>

            <div x-show="open" x-cloak class="border-t border-white/10 bg-stone-950/95 px-4 py-6 backdrop-blur lg:hidden" @click.outside="open = false">
                <div class="flex flex-col gap-4 text-white">
                    @if(Route::has('portal.rooms.index'))
                        <a href="{{ route('portal.rooms.index') }}" wire:navigate @click="open=false">{{ __('portal.nav.rooms') }}</a>
                    @endif
                    @if(Route::has('portal.restaurant.menu'))
                        <a href="{{ route('portal.restaurant.menu') }}" wire:navigate @click="open=false">{{ __('portal.nav.restaurant') }}</a>
                    @endif
                    @if(Route::has('portal.promotions.index'))
                        <a href="{{ route('portal.promotions.index') }}" wire:navigate @click="open=false">{{ __('portal.nav.promotions') }}</a>
                    @endif
                    @if(Route::has('portal.faq.index'))
                        <a href="{{ route('portal.faq.index') }}" wire:navigate @click="open=false">{{ __('portal.nav.faq') }}</a>
                    @endif
                    @if(Route::has('portal.contact.index'))
                        <a href="{{ route('portal.contact.index') }}" wire:navigate @click="open=false">{{ __('portal.nav.contact') }}</a>
                    @endif
                    @auth('guest')
                        <a href="{{ route('portal.dashboard') }}" wire:navigate @click="open=false">{{ __('portal.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('guest.login') }}" wire:navigate @click="open=false">{{ __('portal.nav.login') }}</a>
                        <a href="{{ route('guest.register') }}" wire:navigate @click="open=false">{{ __('portal.nav.register') }}</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-stone-950 text-stone-300">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div>
                    <div class="font-display text-3xl text-white">{{ $portalProperty->name ?? '' }}</div>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-stone-400">{{ $portalProperty->tagline ?? $portalProperty->about }}</p>
                </div>
                <div class="text-sm space-y-2">
                    <div class="font-semibold text-white">{{ __('portal.home.contact') }}</div>
                    @if($portalProperty->address ?? null)<p>{{ $portalProperty->address }}</p>@endif
                    @if($portalProperty->phone ?? null)<p>{{ $portalProperty->phone }}</p>@endif
                    @if($portalProperty->email ?? null)<p>{{ $portalProperty->email }}</p>@endif
                </div>
                <div class="text-sm space-y-2">
                    @if(Route::has('portal.rooms.index'))
                        <a href="{{ route('portal.rooms.index') }}" wire:navigate class="block hover:text-white">{{ __('portal.nav.rooms') }}</a>
                    @endif
                    @if(Route::has('portal.restaurant.menu'))
                        <a href="{{ route('portal.restaurant.menu') }}" wire:navigate class="block hover:text-white">{{ __('portal.nav.restaurant') }}</a>
                    @endif
                    <a href="{{ route('login') }}" class="block text-stone-500 hover:text-stone-300">Staff login</a>
                </div>
            </div>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
