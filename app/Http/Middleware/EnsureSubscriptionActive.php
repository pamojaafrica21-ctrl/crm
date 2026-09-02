<?php

namespace App\Http\Middleware;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Services\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(private OrganizationContext $organizationContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_super_admin) {
            return $next($request);
        }

        if ($request->routeIs('billing', 'billing.*', 'logout', 'profile')) {
            return $next($request);
        }

        $org = $this->organizationContext->resolveForUser($user);

        if (! $org instanceof Organization) {
            return $next($request);
        }

        if ($org->canAccessPlatform()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(402, 'Your subscription has expired. Please renew to continue.');
        }

        if (method_exists($user, 'canManageBilling') && $user->canManageBilling()) {
            return redirect()->route('billing');
        }

        return redirect()->route('billing')->with(
            'billing_notice',
            'Your organisation subscription has expired. Ask the account owner or someone with billing access to renew.'
        );
    }
}
