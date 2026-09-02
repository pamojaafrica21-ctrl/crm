<?php

namespace App\Http\Middleware;

use App\Domain\Organizations\Services\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function __construct(private OrganizationContext $organizationContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (! $user->organization_id) {
            abort(403, 'No organization assigned to your account.');
        }

        $this->organizationContext->resolveForUser($user);

        return $next($request);
    }
}
