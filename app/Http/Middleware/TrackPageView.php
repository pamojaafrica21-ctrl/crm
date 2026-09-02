<?php

namespace App\Http\Middleware;

use App\Domain\Analytics\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        try {
            $ua = substr((string) $request->userAgent(), 0, 500);
            $path = '/'.ltrim($request->path(), '/');
            if ($path === '//') {
                $path = '/';
            }

            PageView::create([
                'path' => substr($path === '/' ? '/' : $path, 0, 500),
                'route_name' => optional($request->route())->getName(),
                'visitor_hash' => hash('sha256', $request->ip().'|'.strtolower($ua).'|'.now()->format('Y-m-d')),
                'referrer' => substr((string) $request->headers->get('referer'), 0, 1000) ?: null,
                'user_agent' => $ua ?: null,
                'is_bot' => $this->isBot($ua),
                'user_id' => $request->user()?->id,
                'visited_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never break the request for analytics.
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $path = $request->path();

        if ($path === '/' || $request->routeIs('home', 'login', 'register', 'password.*')) {
            return true;
        }

        // Skip app, admin, webhooks, assets.
        if (
            $request->is([
                'admin', 'admin/*', 'dashboard', 'dashboard/*',
                'livewire/*', 'stripe/*', 'mpesa/*',
                'robots.txt', 'sitemap.xml', 'llms.txt', 'up',
                'api/*', 'storage/*',
            ])
            || str_contains($path, '.')
        ) {
            return false;
        }

        return false;
    }

    private function isBot(string $ua): bool
    {
        if ($ua === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|preview|wget|curl|python|scrapy|bingpreview|gptbot|claudebot|bytespider|ccbot|perplexity/i',
            $ua
        );
    }
}
