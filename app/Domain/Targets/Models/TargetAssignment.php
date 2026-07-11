<?php

namespace App\Domain\Targets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetAssignment extends Model
{
    protected $fillable = [
        'target_id',
        'user_id',
        'assigned_value',
        'actual_value',
    ];

    protected function casts(): array
    {
        return [
            'assigned_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievementPercentage(): float
    {
        if ($this->assigned_value <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->actual_value / (float) $this->assigned_value) * 100, 1));
    }
}
