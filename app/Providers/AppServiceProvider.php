<?php

namespace App\Providers;

use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PropertyContext::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Administrator')) {
                return true;
            }
        });

        Event::listen(Login::class, function (Login $event): void {
            activity()
                ->causedBy($event->user)
                ->log('User logged in');
        });
    }
}
