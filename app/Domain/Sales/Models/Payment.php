<?php

namespace App\Domain\Sales\Models;

use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToProperty;

    protected $fillable = [
        'property_id',
        'invoice_id',
        'recorded_by',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment): void {
            $payment->invoice?->refreshPaymentStatus();
        });

        static::deleted(function (Payment $payment): void {
            $payment->invoice?->refreshPaymentStatus();
        });
    }
}
