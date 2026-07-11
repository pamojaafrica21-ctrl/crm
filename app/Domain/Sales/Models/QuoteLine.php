<?php

namespace App\Domain\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected $fillable = [
        'quote_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteLine $line): void {
            $line->line_total = $line->quantity * $line->unit_price;
        });
    }
}
