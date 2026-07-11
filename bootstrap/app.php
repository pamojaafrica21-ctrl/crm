<?php

use App\Http\Middleware\EnsurePropertyAccess;
use App\Http\Middleware\SetActiveProperty;
use App\Http\Middleware\SetPortalLocale;
use App\Http\Middleware\SetPortalProperty;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'property' => SetActiveProperty::class,
            'property.access' => EnsurePropertyAccess::class,
            'portal.property' => SetPortalProperty::class,
            'portal.locale' => SetPortalLocale::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('guest/*') || $request->is('account/*')) {
                return route('guest.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('guest/*')) {
                return route('portal.dashboard');
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
