<?php

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
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'mpesa/callback',
        ]);

        $middleware->alias([
            'property' => \App\Http\Middleware\SetActiveProperty::class,
            'property.access' => \App\Http\Middleware\EnsurePropertyAccess::class,
            'organization' => \App\Http\Middleware\EnsureOrganizationAccess::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'super-admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureOrganizationAccess::class,
            \App\Http\Middleware\SetActiveProperty::class,
            \App\Http\Middleware\EnsurePropertyAccess::class,
            \App\Http\Middleware\EnsureSubscriptionActive::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
