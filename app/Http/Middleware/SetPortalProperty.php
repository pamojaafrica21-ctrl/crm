<?php

namespace App\Http\Middleware;

use App\Domain\Properties\Models\Property;
use App\Domain\Properties\Services\PropertyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPortalProperty
{
    public function __construct(private PropertyContext $propertyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $propertyId = config('portal.property_id');

        if ($propertyId) {
            $property = Property::query()
                ->where('id', $propertyId)
                ->where('is_active', true)
                ->first();
        } else {
            $property = Property::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (! $property) {
            abort(503, 'No active property configured for the guest portal.');
        }

        $this->propertyContext->set($property->id);
        $request->attributes->set('portal_property', $property);

        view()->share('portalProperty', $property);

        return $next($request);
    }
}
