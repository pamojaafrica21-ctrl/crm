<?php

namespace App\Http\Middleware;

use App\Domain\Properties\Services\PropertyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyAccess
{
    public function __construct(private PropertyContext $propertyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $propertyId = $this->propertyContext->id();
        $user = $request->user();

        if ($propertyId && $user instanceof \App\Models\User && ! $user->hasPropertyAccess($propertyId)) {
            abort(403, 'You do not have access to this property.');
        }

        return $next($request);
    }
}
