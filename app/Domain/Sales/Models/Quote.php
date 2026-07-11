<?php

namespace App\Domain\Sales\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Quote extends Model
{
    use BelongsToProperty, LogsActivity;

    protected $fillable = [
        'property_id',
        'customer_id',
        'created_by',
        'quote_number',
        'status',
        'issue_date',
        'valid_until',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('sort_order');
    }

    public function convertedInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines->sum('line_total');
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $this->tax_amount,
        ]);
    }
}
