<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Properties\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'property_id',
        'created_by',
        'title',
        'body',
        'target_roles',
        'target_department_ids',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'target_department_ids' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
