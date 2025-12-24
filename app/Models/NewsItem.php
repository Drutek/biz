<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsItem extends Model
{
    /** @use HasFactory<\Database\Factories\NewsItemFactory> */
    use HasFactory;

    protected $fillable = [
        'tracked_entity_id',
        'title',
        'snippet',
        'url',
        'source',
        'published_at',
        'fetched_at',
        'is_read',
        'is_relevant',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'is_read' => 'boolean',
            'is_relevant' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TrackedEntity, $this>
     */
    public function trackedEntity(): BelongsTo
    {
        return $this->belongsTo(TrackedEntity::class);
    }

    /**
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeRelevant(Builder $query): Builder
    {
        return $query->where('is_relevant', true);
    }

    /**
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('fetched_at', '>=', now()->subDays($days));
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function dismiss(): void
    {
        $this->update(['is_relevant' => false]);
    }
}
