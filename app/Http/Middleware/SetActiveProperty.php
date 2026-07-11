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
                abort(403, 'No property access assigned.');
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
