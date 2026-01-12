<?php

namespace App\Models;

use App\Enums\LinkedInPostType;
use App\Enums\LinkedInTone;
use App\Enums\LLMProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedInPost extends Model
{
    /** @use HasFactory<\Database\Factories\LinkedInPostFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'news_item_id',
        'post_type',
        'tone',
        'title',
        'content',
        'hashtags',
        'call_to_action',
        'is_used',
        'is_dismissed',
        'provider',
        'model',
        'tokens_used',
        'generated_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'post_type' => LinkedInPostType::class,
            'tone' => LinkedInTone::class,
            'hashtags' => 'array',
            'is_used' => 'boolean',
            'is_dismissed' => 'boolean',
            'provider' => LLMProvider::class,
            'tokens_used' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<NewsItem, $this>
     */
    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }

    /**
     * @param  Builder<LinkedInPost>  $query
     * @return Builder<LinkedInPost>
     */
    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false)->where('is_dismissed', false);
    }

    /**
     * @param  Builder<LinkedInPost>  $query
     * @return Builder<LinkedInPost>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * @param  Builder<LinkedInPost>  $query
     * @return Builder<LinkedInPost>
     */
    public function scopeForType(Builder $query, LinkedInPostType $type): Builder
    {
        return $query->where('post_type', $type);
    }

    /**
     * @param  Builder<LinkedInPost>  $query
     * @return Builder<LinkedInPost>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    public function dismiss(): void
    {
        $this->update(['is_dismissed' => true]);
    }
}
