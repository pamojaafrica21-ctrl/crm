<?php

namespace App\Domain\Billing\Models;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'interval',
        'stripe_product_id',
        'stripe_price_id',
        'trial_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SubscriptionPlan $plan): void {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function formattedPrice(): string
    {
        return strtoupper($this->currency).' '.number_format((float) $this->price, 2);
    }

    public function featureLimit(string $key, mixed $default = null): mixed
    {
        return data_get($this->features, $key, $default);
    }

    public function highlightItems(): array
    {
        $items = data_get($this->features, 'highlights', []);

        return is_array($items) ? array_values(array_filter($items, fn ($item) => filled($item))) : [];
    }

    public function moduleList(): array
    {
        $modules = data_get($this->features, 'modules', []);

        return is_array($modules) ? array_values($modules) : [];
    }

    public function isFeatured(): bool
    {
        return (bool) data_get($this->features, 'is_featured', false);
    }

    public function badge(): ?string
    {
        $badge = data_get($this->features, 'badge');

        return filled($badge) ? (string) $badge : null;
    }

    public function ctaLabel(): string
    {
        return (string) data_get($this->features, 'cta_label', 'Start free trial');
    }
}
