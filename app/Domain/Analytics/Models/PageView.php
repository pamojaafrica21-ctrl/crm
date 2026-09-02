<?php

namespace App\Domain\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path',
        'route_name',
        'visitor_hash',
        'referrer',
        'user_agent',
        'is_bot',
        'user_id',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function humans(): Builder
    {
        return static::query()->where('is_bot', false);
    }

    public static function summary(?\DateTimeInterface $from = null): array
    {
        $from = $from ?? now()->subDays(30)->startOfDay();

        $distinctVisitors = fn (Builder $query) => (int) $query->selectRaw('COUNT(DISTINCT visitor_hash) as aggregate')->value('aggregate');

        $base = static::humans()->where('visited_at', '>=', $from);

        $byDay = (clone $base)
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topPaths = (clone $base)
            ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return [
            'views_today' => static::humans()->where('visited_at', '>=', now()->startOfDay())->count(),
            'visitors_today' => $distinctVisitors(static::humans()->where('visited_at', '>=', now()->startOfDay())),
            'views_7d' => static::humans()->where('visited_at', '>=', now()->subDays(7))->count(),
            'visitors_7d' => $distinctVisitors(static::humans()->where('visited_at', '>=', now()->subDays(7))),
            'views_30d' => (clone $base)->count(),
            'visitors_30d' => $distinctVisitors(clone $base),
            'by_day' => $byDay,
            'top_paths' => $topPaths,
        ];
    }
}
