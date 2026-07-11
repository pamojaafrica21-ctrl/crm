<?php

namespace App\Domain\Dashboard\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiSnapshot extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'metric_key',
        'period_date',
        'period_type',
        'value',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'value' => 'decimal:2',
            'dimensions' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
