<?php

namespace App\Domain\Customers\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Rooms\Models\ExtraService;
use App\Domain\Rooms\Models\Room;
use App\Domain\Rooms\Models\RoomType;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Domain\Shared\Traits\BelongsToProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'customer_id',
        'room_type_id',
        'room_id',
        'invoice_id',
        'confirmation_number',
        'room_type',
        'room_number',
        'check_in',
        'check_out',
        'status',
        'total_amount',
        'tax_amount',
        'extras_amount',
        'discount_amount',
        'coupon_code',
        'currency',
        'guests',
        'adults',
        'children',
        'special_requests',
        'source',
        'group_code',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'extras_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'synced_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    public function nights(): int
    {
        return max(1, $this->check_in->diffInDays($this->check_out));
    }

    public function grandTotal(): float
    {
        return (float) $this->total_amount;
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function extraServices(): BelongsToMany
    {
        return $this->belongsToMany(ExtraService::class, 'reservation_extra_service')
            ->withPivot(['quantity', 'unit_price', 'total'])
            ->withTimestamps();
    }
}
