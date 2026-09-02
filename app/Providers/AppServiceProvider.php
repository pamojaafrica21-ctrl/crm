<?php

namespace App\Providers;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Services\OrganizationContext;
use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PropertyContext::class);
        $this->app->singleton(OrganizationContext::class);
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Organization::class);
        Cashier::ignoreRoutes();

        Gate::before(function ($user, $ability) {
            if ($user->is_super_admin ?? false) {
                return true;
            }

            // Organisation owner can always manage billing for their company.
            if (in_array($ability, ['billing.manage', 'billing.view'], true)
                && method_exists($user, 'organization')
                && $user->organization?->isOwnedBy($user)
            ) {
                return true;
            }

            // Org administrators always have full access within their organization.
            if (
                method_exists($user, 'hasRole')
                && (
                    $user->hasRole('Administrator')
                    || $user->roles()->where('name', 'Administrator')->exists()
                )
            ) {
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
