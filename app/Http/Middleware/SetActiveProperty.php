<?php

namespace App\Http\Middleware;

use App\Domain\Properties\Services\PropertyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveProperty
{
    public function __construct(private PropertyContext $propertyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $available = $this->propertyContext->availableForUser($user);

            if ($available->isEmpty()) {
                // Administrators: ensure a default property exists so they are never locked out.
                if ($user->hasRole('Administrator')) {
                    $property = \App\Domain\Properties\Models\Property::firstOrCreate(
                        ['code' => 'MR'],
                        [
                            'name' => 'Montana Resort',
                            'timezone' => 'Africa/Nairobi',
                            'currency' => 'USD',
                            'address' => 'Montana Resort',
                            'is_active' => true,
                        ]
                    );

                    if (! $property->is_active) {
                        $property->update(['is_active' => true]);
                    }

                    $user->properties()->syncWithoutDetaching([$property->id]);
                    $available = collect([$property]);
                } else {
                    abort(403, 'No property access assigned. Ask an administrator to grant you Montana Resort access.');
                }
            }

            $activeId = session('active_property_id');

            if (! $activeId || ! $available->contains('id', (int) $activeId)) {
                $this->propertyContext->set($available->first()->id);
            } else {
                $this->propertyContext->set((int) $activeId);
            }
        }

        return $next($request);
    }
}
