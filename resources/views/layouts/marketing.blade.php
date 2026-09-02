<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />
    <x-seo :title="$title ?? null" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|instrument-serif:400,400i&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --ink: #0d2a22;
            --ink-soft: #1f4a3c;
            --mist: #e7eee9;
            --paper: #f4f7f5;
            --line: rgba(13, 42, 34, 0.12);
            --accent: #2f6b55;
        }
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Instrument Serif', ui-serif, Georgia, serif; }
        @keyframes rise {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes sheen {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        .animate-rise { animation: rise .85s cubic-bezier(.22,1,.36,1) both; }
        .animate-fade { animation: fade 1s ease both; }
        .animate-float { animation: floaty 9s ease-in-out infinite; }
        .hero-sheen {
            background-size: 200% 200%;
            animation: sheen 14s ease-in-out infinite alternate;
        }
        .plan-select:hover { transform: translateY(-2px); }
        .plan-select { transition: transform .25s ease, border-color .25s ease, background-color .25s ease; }
        @media (prefers-reduced-motion: reduce) {
            .animate-rise, .animate-fade, .animate-float, .hero-sheen { animation: none !important; }
        }
    </style>
</head>
<body class="antialiased text-[var(--ink)] bg-[var(--paper)]">
    {{ $slot }}
    @livewireScripts
</body>
</html>
