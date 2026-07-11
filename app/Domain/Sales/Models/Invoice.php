<?php

namespace App\Domain\Sales\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Properties\Models\Property;
use App\Domain\Shared\Enums\InvoiceStatus;
use App\Domain\Shared\Traits\BelongsToProperty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Invoice extends Model
{
    use BelongsToProperty, LogsActivity;

    protected $fillable = [
        'property_id',
        'customer_id',
        'quote_id',
        'created_by',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'currency',
        'notes',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function outstandingBalance(): float
    {
        return (float) $this->total_amount - (float) $this->amount_paid;
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines->sum('line_total');
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $this->tax_amount,
        ]);
    }

    public function refreshPaymentStatus(): void
    {
        $paid = $this->payments()->sum('amount');
        $status = match (true) {
            $paid <= 0 => InvoiceStatus::Sent,
            $paid >= $this->total_amount => InvoiceStatus::Paid,
            default => InvoiceStatus::Partial,
        };

        $this->update([
            'amount_paid' => $paid,
            'status' => $status,
        ]);
    }
}
