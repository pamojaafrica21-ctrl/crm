<?php

namespace App\Domain\Shared\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalMapping extends Model
{
    protected $fillable = [
        'property_id',
        'entity_type',
        'crm_id',
        'external_system',
        'external_id',
        'external_data',
        'last_synced_at',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'external_data' => 'array',
            'last_synced_at' => 'datetime',
            'sync_status' => SyncStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
