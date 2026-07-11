<?php

namespace App\Domain\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCommunication extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'channel',
        'direction',
        'subject',
        'body',
        'communicated_at',
    ];

    protected function casts(): array
    {
        return ['communicated_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
