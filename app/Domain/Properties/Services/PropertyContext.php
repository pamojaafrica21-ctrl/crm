<?php

namespace App\Domain\Properties\Services;

use App\Domain\Properties\Models\Property;
use Illuminate\Support\Collection;

class PropertyContext
{
    private ?int $propertyId = null;

    private bool $applyScope = true;

    public function set(int $propertyId): void
    {
        $this->propertyId = $propertyId;
        session(['active_property_id' => $propertyId]);
    }

    public function id(): ?int
    {
        if ($this->propertyId) {
            return $this->propertyId;
        }

        $sessionId = session('active_property_id');

        return $sessionId ? (int) $sessionId : null;
    }

    public function property(): ?Property
    {
        $id = $this->id();

        return $id ? Property::find($id) : null;
    }

    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->applyScope;
        $this->applyScope = false;

        try {
            return $callback();
        } finally {
            $this->applyScope = $previous;
        }
    }

    public function shouldApplyScope(): bool
    {
        return $this->applyScope;
    }

    public function availableForUser(?\App\Models\User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($user->hasRole('Administrator')) {
            $active = Property::where('is_active', true)->orderBy('name')->get();

            if ($active->isNotEmpty()) {
                return $active;
            }

            // Admins should never be locked out if properties exist but were deactivated.
            return Property::orderBy('name')->get();
        }

        return $user->properties()->where('is_active', true)->orderBy('name')->get();
    }
}
