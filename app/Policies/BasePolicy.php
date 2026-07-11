<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    protected function hasPropertyAccess(User $user, Model $model): bool
    {
        if (! isset($model->property_id)) {
            return true;
        }

        return $user->hasPropertyAccess($model->property_id);
    }

    protected function canWithProperty(User $user, string $permission, ?int $propertyId = null): bool
    {
        if (! $user->can($permission)) {
            return false;
        }

        if ($propertyId === null) {
            return true;
        }

        return $user->hasPropertyAccess($propertyId);
    }
}
