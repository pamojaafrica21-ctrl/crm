<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Properties\Services\PropertyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToProperty
{
    public static function bootBelongsToProperty(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->property_id && app()->bound(PropertyContext::class)) {
                $propertyId = app(PropertyContext::class)->id();
                if ($propertyId) {
                    $model->property_id = $propertyId;
                }
            }
        });

        static::addGlobalScope('property', function (Builder $builder): void {
            if (! app()->bound(PropertyContext::class)) {
                return;
            }

            $context = app(PropertyContext::class);

            if ($context->shouldApplyScope()) {
                $propertyId = $context->id();
                if ($propertyId) {
                    $builder->where($builder->getModel()->getTable().'.property_id', $propertyId);
                }
            }
        });
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->withoutGlobalScope('property')->where('property_id', $propertyId);
    }
}
