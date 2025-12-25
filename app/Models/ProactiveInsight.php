<?php

namespace App\Models;

use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Enums\LLMProvider;
use App\Enums\TriggerType;
use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProactiveInsight extends Model
{
    /** @use HasFactory<\Database\Factories\ProactiveInsightFactory> */
    use HasEmbedding;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'trigger_type',
        'trigger_context',
        'insight_type',
        'title',
        'content',
        'priority',
        'is_read',
        'is_dismissed',
        'related_event_id',
        'provider',
        'model',
        'tokens_used',
        'is_embedded',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'trigger_type' => TriggerType::class,
            'trigger_context' => 'array',
            'insight_type' => InsightType::class,
            'priority' => InsightPriority::class,
            'is_read' => 'boolean',
            'is_dismissed' => 'boolean',
            'provider' => LLMProvider::class,
            'tokens_used' => 'integer',
            'is_embedded' => 'boolean',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getEmbeddableColumns(): array
    {
        return ['title', 'content'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BusinessEvent, $this>
     */
    public function relatedEvent(): BelongsTo
    {
        return $this->belongsTo(BusinessEvent::class, 'related_event_id');
    }

    /**
     * @param  Builder<ProactiveInsight>  $query
     * @return Builder<ProactiveInsight>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * @param  Builder<ProactiveInsight>  $query
     * @return Builder<ProactiveInsight>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * @param  Builder<ProactiveInsight>  $query
     * @return Builder<ProactiveInsight>
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', InsightPriority::Urgent);
    }

    /**
     * @param  Builder<ProactiveInsight>  $query
     * @return Builder<ProactiveInsight>
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [InsightPriority::High, InsightPriority::Urgent]);
    }

    /**
     * @param  Builder<ProactiveInsight>  $query
     * @return Builder<ProactiveInsight>
     */
    public function scopeByType(Builder $query, InsightType $type): Builder
    {
        return $query->where('insight_type', $type);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function dismiss(): void
    {
        $this->update(['is_dismissed' => true]);
    }

    public function isActionable(): bool
    {
        return ! $this->is_read && ! $this->is_dismissed;
    }
}
