<?php

namespace App\Domain\Appointments\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Organizations\Models\Department;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\AppointmentStatus;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Appointment extends Model
{
    use BelongsToProperty, LogsActivity;

    protected $fillable = [
        'property_id',
        'appointment_type_id',
        'customer_id',
        'assigned_to',
        'created_by',
        'title',
        'notes',
        'starts_at',
        'ends_at',
        'status',
        'location',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'reminder_sent_at' => 'datetime',
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'appointment_department')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
