<?php

namespace App\Domain\Rooms\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExtraService extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'name',
        'slug',
        'description',
        'price',
        'pricing_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExtraService $service): void {
            if (! $service->slug) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function calculateTotal(int $nights, int $quantity = 1): float
    {
        $unit = (float) $this->price;

        return match ($this->pricing_type) {
            'per_night' => $unit * $nights * $quantity,
            'per_unit' => $unit * $quantity,
            default => $unit * $quantity,
        };
    }
}
