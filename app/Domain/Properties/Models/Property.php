<?php

namespace App\Domain\Properties\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'timezone',
        'currency',
        'address',
        'tagline',
        'about',
        'phone',
        'email',
        'website',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'hero_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('hero')->singleFile();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_properties')->withTimestamps();
    }
}
