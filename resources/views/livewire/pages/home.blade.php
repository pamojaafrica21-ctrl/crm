<?php

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Services\HomepageContentService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.marketing')] class extends Component
{
    public function with(): array
    {
        $homepage = app(HomepageContentService::class);
        $trialDays = $homepage->trialDays();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($trialDays <= 0 && $plans->isNotEmpty()) {
            $trialDays = (int) $plans->min('trial_days');
        }

        $features = $homepage->features();
        $heroImage = $features[0]['image'] ?? '/images/marketing/crm-dashboard.png';

        return [
            'hero' => $homepage->hero(),
            'features' => $features,
            'heroImage' => $heroImage,
            'plans' => $plans,
            'trialDays' => max(1, $trialDays),
            'appName' => config('app.name', 'Core CRM'),
        ];
    }
}; ?>

<div class="overflow-x-hidden">
    {{-- Transparent nav over hero --}}
    <header class="absolute inset-x-0 top-0 z-30">
        <div class="mx-auto max-w-6xl px-6 py-5 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-white" wire:navigate>
                <img src="{{ asset('favicon-32x32.png') }}" alt="" width="36" height="36" class="w-9 h-9 rounded-lg ring-1 ring-white/25">
                <span class="font-display text-xl tracking-tight">{{ $appName }}</span>
            </a>
            <nav class="hidden md:flex items-center gap-8 text-sm text-white/75">
                <a href="#product" class="hover:text-white transition">Product</a>
                <a href="#pricing" class="hover:text-white transition">Pricing</a>
                <a href="{{ route('login') }}" wire:navigate class="hover:text-white transition">Sign in</a>
            </nav>
            <a href="{{ route('register') }}" wire:navigate
               class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] hover:bg-[var(--mist)] transition">
                Free {{ $trialDays }}-day trial
            </a>
        </div>
    </header>

    {{-- Hero: one composition, brand first, full-bleed product plane --}}
    <section class="relative min-h-[100svh] flex flex-col justify-end overflow-hidden text-white">
        <div class="absolute inset-0 hero-sheen -z-20"
             style="background-image:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(90,150,120,.35), transparent 55%),
                radial-gradient(ellipse 60% 50% at 85% 80%, rgba(20,60,45,.55), transparent 50%),
                linear-gradient(155deg, #124536 0%, #0a241c 42%, #163d30 100%);">
        </div>
        <div class="absolute inset-0 -z-10 opacity-[0.14]"
             style="background-image:url('data:image/svg+xml,%3Csvg width=\"72\" height=\"72\" viewBox=\"0 0 72 72\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cpath d=\"M36 0L72 36 36 72 0 36z\" fill=\"none\" stroke=\"%23ffffff\" stroke-width=\"0.6\"/%3E%3C/svg%3E');">
        </div>

        <div class="mx-auto w-full max-w-6xl px-6 pt-28 pb-10 lg:pb-12">
            <h1 class="animate-rise font-display text-[clamp(3.25rem,9vw,6.75rem)] leading-[0.92] tracking-tight max-w-4xl">
                {{ $appName }}
            </h1>
            <p class="animate-rise mt-6 max-w-xl text-lg sm:text-xl text-white/75 leading-relaxed" style="animation-delay:.12s">
                {{ $hero['headline'] }}
            </p>
            <div class="animate-rise mt-8 flex flex-wrap items-center gap-4" style="animation-delay:.22s">
                <a href="{{ route('register') }}" wire:navigate
                   class="inline-flex items-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[var(--ink)] hover:bg-[var(--mist)] transition">
                    {{ $hero['cta_label'] }} · {{ $trialDays }} days free
                </a>
                <a href="#pricing" class="text-sm font-medium text-white/70 hover:text-white underline-offset-4 hover:underline transition">
                    View plans
                </a>
            </div>
        </div>

        {{-- Dominant edge-to-edge product visual --}}
        <div class="animate-fade relative w-full mt-2" style="animation-delay:.35s">
            <div class="mx-auto max-w-6xl px-6">
                <div class="animate-float relative overflow-hidden rounded-t-[1.25rem] ring-1 ring-white/20 bg-[#0a1f18] shadow-[0_-20px_80px_-20px_rgba(0,0,0,.45)]">
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10">
                        <span class="w-2.5 h-2.5 rounded-full bg-white/25"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-white/25"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-white/25"></span>
                        <span class="ml-3 text-xs text-white/40">{{ $appName }}</span>
                    </div>
                    <img src="{{ $heroImage }}" alt="{{ $appName }} product preview" class="w-full h-auto max-h-[46vh] object-cover object-top">
                </div>
            </div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[var(--paper)] to-transparent"></div>
        </div>
    </section>

    {{-- Trial clarity strip: one job --}}
    <section class="border-y border-[var(--line)] bg-[var(--mist)]">
        <div class="mx-auto max-w-6xl px-6 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <p class="font-display text-3xl sm:text-4xl text-[var(--ink)]">{{ $trialDays }} days free</p>
                <p class="mt-2 text-[var(--ink-soft)]/80 max-w-md">Create your organisation and explore the full hospitality CRM — guests, quotes, invoices, reports, and property operations. No card required to start.</p>
            </div>
            <a href="{{ route('register') }}" wire:navigate class="text-sm font-semibold text-[var(--accent)] hover:text-[var(--ink)] underline-offset-4 hover:underline">
                Start your free trial →
            </a>
        </div>
    </section>

    {{-- Product features: one section each, large visual anchor --}}
    <section id="product" class="mx-auto max-w-6xl px-6 py-20 sm:py-28 space-y-28">
        <div class="max-w-2xl">
            <h2 class="font-display text-4xl sm:text-5xl leading-tight text-[var(--ink)]">Hotel &amp; resort CRM built for property teams</h2>
            <p class="mt-4 text-lg text-[var(--ink-soft)]/75 leading-relaxed">Guest relationship management, sales quotes, invoices, staff tasks, and detailed reporting — organised by property for hotels, resorts, lodges, and guest houses.</p>
        </div>

        @foreach ($features as $index => $feature)
            <article class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                <div class="lg:col-span-5 {{ $index % 2 === 1 ? 'lg:order-2' : '' }}">
                    <p class="text-xs font-semibold tracking-[0.18em] uppercase text-[var(--accent)]">0{{ $index + 1 }}</p>
                    <h3 class="font-display mt-3 text-3xl sm:text-4xl text-[var(--ink)] leading-tight">{{ $feature['title'] }}</h3>
                    <p class="mt-4 text-[var(--ink-soft)]/80 leading-relaxed text-base sm:text-lg">{{ $feature['description'] }}</p>
                </div>
                <div class="lg:col-span-7 {{ $index % 2 === 1 ? 'lg:order-1' : '' }}">
                    <div class="overflow-hidden rounded-2xl ring-1 ring-[var(--line)] bg-white">
                        <img
                            src="{{ $feature['image'] }}"
                            alt="{{ $feature['title'] }}"
                            class="w-full h-auto object-cover"
                            loading="lazy"
                        >
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    {{-- Pricing: interactive plan selection (cards allowed) --}}
    <section id="pricing" class="relative">
        <div class="absolute inset-0 -z-10"
             style="background:
                radial-gradient(ellipse 50% 40% at 50% 0%, rgba(47,107,85,.08), transparent 70%),
                var(--paper);">
        </div>
        <div class="mx-auto max-w-6xl px-6 py-20 sm:py-28">
            <div class="max-w-2xl mb-12">
                <h2 class="font-display text-4xl sm:text-5xl text-[var(--ink)] leading-tight">Simple plans that grow with you</h2>
                <p class="mt-4 text-lg text-[var(--ink-soft)]/75">
                    Every plan includes a free trial. Prices and features update live from the admin centre.
                </p>
            </div>

            @if ($plans->isEmpty())
                <p class="text-[var(--ink-soft)]/70">Plans will appear here once published by the platform admin.</p>
            @else
                <div class="grid md:grid-cols-3 gap-5">
                    @foreach ($plans as $plan)
                        @php
                            $trial = $plan->trial_days > 0 ? $plan->trial_days : $trialDays;
                            $featured = $plan->isFeatured();
                        @endphp
                        <div class="plan-select flex flex-col rounded-2xl p-7 ring-1
                            {{ $featured
                                ? 'bg-[var(--ink)] text-white ring-[var(--ink)]'
                                : 'bg-white/80 text-[var(--ink)] ring-[var(--line)]' }}">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="text-lg font-semibold tracking-tight">{{ $plan->name }}</h3>
                                @if ($plan->badge())
                                    <span class="text-xs font-medium {{ $featured ? 'text-white/70' : 'text-[var(--accent)]' }}">
                                        {{ $plan->badge() }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm leading-relaxed {{ $featured ? 'text-white/65' : 'text-[var(--ink-soft)]/70' }}">
                                {{ $plan->description }}
                            </p>
                            <p class="mt-6 font-display text-4xl tracking-tight">
                                {{ $plan->formattedPrice() }}
                                <span class="text-base font-sans font-normal {{ $featured ? 'text-white/55' : 'text-[var(--ink-soft)]/55' }}">
                                    /{{ $plan->interval }}
                                </span>
                            </p>
                            <p class="mt-2 text-sm font-medium {{ $featured ? 'text-emerald-200/90' : 'text-[var(--accent)]' }}">
                                {{ $trial }}-day free trial
                            </p>
                            <ul class="mt-6 space-y-2.5 text-sm flex-1 {{ $featured ? 'text-white/75' : 'text-[var(--ink-soft)]/80' }}">
                                @forelse ($plan->highlightItems() as $item)
                                    <li class="flex gap-2.5">
                                        <span class="mt-0.5 {{ $featured ? 'text-emerald-300' : 'text-[var(--accent)]' }}" aria-hidden="true">—</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @empty
                                    <li class="flex gap-2.5">
                                        <span class="mt-0.5 {{ $featured ? 'text-emerald-300' : 'text-[var(--accent)]' }}" aria-hidden="true">—</span>
                                        <span>Up to {{ $plan->featureLimit('max_users') ?? 'unlimited' }} users</span>
                                    </li>
                                    <li class="flex gap-2.5">
                                        <span class="mt-0.5 {{ $featured ? 'text-emerald-300' : 'text-[var(--accent)]' }}" aria-hidden="true">—</span>
                                        <span>{{ $plan->featureLimit('max_properties') ?? 'Unlimited' }} properties</span>
                                    </li>
                                @endforelse
                            </ul>
                            <a href="{{ route('register') }}" wire:navigate
                               class="mt-8 inline-flex justify-center rounded-lg px-4 py-3 text-sm font-semibold transition
                               {{ $featured
                                    ? 'bg-white text-[var(--ink)] hover:bg-[var(--mist)]'
                                    : 'bg-[var(--ink)] text-white hover:bg-[var(--ink-soft)]' }}">
                                {{ $plan->ctaLabel() }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="mx-auto max-w-6xl px-6 pb-20 sm:pb-28">
        <div class="relative overflow-hidden rounded-[1.75rem] px-8 py-14 sm:px-14 sm:py-16 text-white"
             style="background:
                radial-gradient(ellipse 70% 80% at 10% 20%, rgba(120,170,140,.25), transparent 50%),
                linear-gradient(135deg, #124536, #0a241c);">
            <h2 class="font-display text-3xl sm:text-5xl leading-tight max-w-xl">
                Ready when your team is.
            </h2>
            <p class="mt-4 max-w-lg text-white/70 text-lg">
                Spin up {{ $appName }} in minutes and run your property operations on a {{ $trialDays }}-day free trial.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('register') }}" wire:navigate
                   class="inline-flex rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[var(--ink)] hover:bg-[var(--mist)] transition">
                    Start free trial
                </a>
                <a href="{{ route('login') }}" wire:navigate
                   class="inline-flex rounded-lg px-5 py-3 text-sm font-semibold text-white/80 ring-1 ring-white/25 hover:bg-white/10 transition">
                    Sign in
                </a>
            </div>
        </div>
    </section>

    <footer class="border-t border-[var(--line)] bg-[var(--mist)]/40">
        <div class="mx-auto max-w-6xl px-6 py-14 sm:py-16">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-1">
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-3">
                        <img src="{{ asset('favicon-32x32.png') }}" alt="" width="32" height="32" class="rounded-md ring-1 ring-[var(--line)]">
                        <span class="font-display text-xl text-[var(--ink)]">{{ $appName }}</span>
                    </a>
                    <p class="mt-4 text-sm leading-relaxed text-[var(--ink-soft)]/75 max-w-xs">
                        Hospitality CRM software for hotels, resorts, and property teams — guests, quotes, invoices, appointments, reports, and operations in one place.
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold tracking-[0.16em] uppercase text-[var(--accent)]">Product</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-[var(--ink-soft)]/80">
                        <li><a href="#product" class="hover:text-[var(--ink)] transition">Features</a></li>
                        <li><a href="#pricing" class="hover:text-[var(--ink)] transition">Pricing &amp; plans</a></li>
                        <li><a href="{{ route('register') }}" wire:navigate class="hover:text-[var(--ink)] transition">Start free trial</a></li>
                        <li><a href="{{ route('sitemap') }}" class="hover:text-[var(--ink)] transition">Sitemap</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-xs font-semibold tracking-[0.16em] uppercase text-[var(--accent)]">Account</p>
                    <ul class="mt-4 space-y-2.5 text-sm text-[var(--ink-soft)]/80">
                        <li><a href="{{ route('login') }}" wire:navigate class="hover:text-[var(--ink)] transition">Sign in</a></li>
                        <li><a href="{{ route('register') }}" wire:navigate class="hover:text-[var(--ink)] transition">Create organisation</a></li>
                    </ul>
                </div>

                <div>
                    <p class="text-xs font-semibold tracking-[0.16em] uppercase text-[var(--accent)]">Parent company</p>
                    <p class="mt-4 font-display text-lg text-[var(--ink)]">{{ config('pamoja.name') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]/75">
                        {{ config('pamoja.tagline') }}
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-[var(--ink-soft)]/80">
                        <li>
                            <a href="{{ config('pamoja.url') }}" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--ink)] transition">
                                Visit {{ config('pamoja.name') }} →
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ config('pamoja.email') }}" class="hover:text-[var(--ink)] transition">
                                {{ config('pamoja.email') }}
                            </a>
                        </li>
                        <li>
                            <a href="tel:{{ config('pamoja.phone_href') }}" class="hover:text-[var(--ink)] transition">
                                {{ config('pamoja.phone') }}
                            </a>
                        </li>
                        <li class="text-[var(--ink-soft)]/65">{{ config('pamoja.location') }}</li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-[var(--line)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-[var(--ink-soft)]/55">
                <p>
                    © {{ date('Y') }} {{ $appName }}. A product of
                    <a href="{{ config('pamoja.url') }}" target="_blank" rel="noopener noreferrer" class="underline-offset-2 hover:underline hover:text-[var(--ink)] transition">
                        {{ config('pamoja.name') }}
                    </a>, {{ config('pamoja.location') }}.
                </p>
                <p>Hotel CRM · Resort guest management · Property operations SaaS</p>
            </div>
        </div>
    </footer>
</div>
