<?php

namespace App\Models;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Properties\Models\Property;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'department', 'is_active', 'organization_id', 'is_super_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'remember_token']);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'user_properties')->withTimestamps();
    }

    public function hasPropertyAccess(int $propertyId): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if ($this->hasRole('Administrator')) {
            $property = Property::find($propertyId);

            return $property && $property->organization_id === $this->organization_id;
        }

        return $this->properties()->where('properties.id', $propertyId)->exists();
    }

    public function canAccessModule(string $permission): bool
    {
        return $this->can($permission);
    }

    public function canManageBilling(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if ($this->organization?->isOwnedBy($this)) {
            return true;
        }

        return $this->can('billing.manage');
    }

    public function canViewBilling(): bool
    {
        return $this->canManageBilling() || $this->can('billing.view');
    }
}
