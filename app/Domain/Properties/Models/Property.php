<?php

namespace App\Domain\Properties\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Property extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'timezone',
        'currency',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_properties')->withTimestamps();
    }
}
