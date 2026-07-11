<?php

namespace App\Domain\Restaurant\Models;

use App\Domain\Customers\Models\FnbOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FnbOrderItem extends Model
{
    protected $fillable = [
        'fnb_order_id',
        'menu_item_id',
        'name',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FnbOrder::class, 'fnb_order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    protected static function booted(): void
    {
        static::saving(function (FnbOrderItem $item): void {
            $item->line_total = (float) $item->unit_price * (int) $item->quantity;
        });
    }
}
