<?php

namespace App\Domain\Targets\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\TargetMetric;
use App\Domain\Shared\Enums\TargetPeriod;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Target extends Model
{
    use BelongsToProperty, LogsActivity;

    protected $fillable = [
        'property_id',
        'created_by',
        'name',
        'metric',
        'period',
        'period_start',
        'period_end',
        'target_value',
        'currency',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'metric' => TargetMetric::class,
            'period' => TargetPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'target_value' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TargetAssignment::class);
    }

    public function totalAssigned(): float
    {
        return (float) $this->assignments->sum('assigned_value');
    }

    public function totalActual(): float
    {
        return (float) $this->assignments->sum('actual_value');
    }

    public function achievementPercentage(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        return min(100, round(($this->totalActual() / (float) $this->target_value) * 100, 1));
    }
}
