<?php

namespace App\Domain\Customers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends Model
{
    protected $fillable = ['customer_id', 'user_id', 'body', 'is_pinned'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
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
