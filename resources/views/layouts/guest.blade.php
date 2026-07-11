<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Montana Resort') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
            <a href="/" wire:navigate class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center font-bold text-sm text-white">MR</div>
                <div>
                    <div class="font-semibold text-sm text-slate-900">{{ config('app.name', 'Montana Resort') }}</div>
                    <div class="text-xs text-slate-500">Staff CRM</div>
                </div>
            </a>

            <div class="w-full sm:max-w-md mt-8 px-6 py-8 bg-white shadow-sm overflow-hidden sm:rounded-xl border border-slate-100">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
