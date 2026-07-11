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

            // When staff access has been customized, direct permissions are the source of truth
            // so give/revoke on the staff page can both grant and remove page/action access.
            if (method_exists($user, 'getDirectPermissions') && $user->getDirectPermissions()->isNotEmpty()) {
                return $user->hasDirectPermission($ability);
            }

            return null;
        });

        Event::listen(Login::class, function (Login $event): void {
            activity()
                ->causedBy($event->user)
                ->log('User logged in');
        });
    }
}
