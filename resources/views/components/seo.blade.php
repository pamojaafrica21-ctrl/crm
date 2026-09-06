@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'canonical' => null,
    'type' => 'website',
])

@php
    $appName = config('app.name', 'Core CRM');
    $pageTitle = $title ?: $appName.' | CRM Software for Every Industry';
    $pageDescription = $description ?: 'Core CRM is a multi-tenant CRM and business operations platform for organisations in every industry. Manage customers, quotes, invoices, staff tasks, appointments, payments, and detailed reports — with a free trial. Built by Pamoja Africa in Nairobi, Kenya.';
    $pageKeywords = $keywords ?: 'Core CRM, CRM software, multi-industry CRM, customer relationship management, sales CRM, invoice and quote software, staff task management CRM, multi-tenant SaaS CRM Africa, Kenya CRM software, free trial CRM, Pamoja Africa';
    $canonicalUrl = $canonical ?: url()->current();
    $ogImage = asset('images/marketing/crm-dashboard.png');
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="keywords" content="{{ $pageKeywords }}">
<meta name="author" content="Pamoja Africa">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" type="text/plain" title="LLM context" href="{{ url('/llms.txt') }}">

<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $appName }} CRM dashboard">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<meta name="application-name" content="{{ $appName }}">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'SoftwareApplication',
            'name' => $appName,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => url('/'),
            'description' => $pageDescription,
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'description' => 'Free trial available',
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => config('pamoja.name'),
                'url' => config('pamoja.url'),
                'email' => config('pamoja.email'),
                'telephone' => config('pamoja.phone'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Nairobi',
                    'addressCountry' => 'KE',
                ],
            ],
            'keywords' => $pageKeywords,
        ],
        [
            '@type' => 'Organization',
            'name' => config('pamoja.name'),
            'url' => config('pamoja.url'),
            'email' => config('pamoja.email'),
            'telephone' => config('pamoja.phone'),
            'description' => config('pamoja.tagline'),
            'sameAs' => [config('pamoja.url')],
            'brand' => [
                '@type' => 'Brand',
                'name' => $appName,
            ],
        ],
        [
            '@type' => 'WebSite',
            'name' => $appName,
            'url' => url('/'),
            'description' => $pageDescription,
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('pamoja.name'),
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
