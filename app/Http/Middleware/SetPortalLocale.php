<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetPortalLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('portal.locales', ['en']);
        $locale = session('portal_locale', config('portal.default_locale', 'en'));

        if ($request->has('lang') && in_array($request->query('lang'), $supported, true)) {
            $locale = $request->query('lang');
            session(['portal_locale' => $locale]);
        }

        if (! in_array($locale, $supported, true)) {
            $locale = config('portal.default_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
