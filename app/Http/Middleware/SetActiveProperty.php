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
            if ($user->is_super_admin) {
                return $next($request);
            }

            $available = $this->propertyContext->availableForUser($user);

            if ($available->isEmpty()) {
                if ($user->hasRole('Administrator') && $user->organization_id) {
                    $property = \App\Domain\Properties\Models\Property::firstOrCreate(
                        [
                            'organization_id' => $user->organization_id,
                            'code' => 'MAIN',
                        ],
                        [
                            'name' => $user->organization?->name ?? 'Main Property',
                            'timezone' => 'Africa/Nairobi',
                            'currency' => 'USD',
                            'address' => '',
                            'is_active' => true,
                        ]
                    );

                    if (! $property->is_active) {
                        $property->update(['is_active' => true]);
                    }

                    $user->properties()->syncWithoutDetaching([$property->id]);
                    $available = collect([$property]);
                } else {
                    abort(403, 'No property access assigned. Ask an administrator to grant you property access.');
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
