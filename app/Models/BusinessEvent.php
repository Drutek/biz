<?php

namespace App\Models;

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BusinessEvent extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessEventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'category',
        'title',
        'description',
        'metadata',
        'significance',
        'eventable_type',
        'eventable_id',
        'occurred_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'category' => EventCategory::class,
            'significance' => EventSignificance::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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
     * @return MorphTo<Model, $this>
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeFinancial(Builder $query): Builder
    {
        return $query->where('category', EventCategory::Financial);
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeMarket(Builder $query): Builder
    {
        return $query->where('category', EventCategory::Market);
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeAdvisory(Builder $query): Builder
    {
        return $query->where('category', EventCategory::Advisory);
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeBySignificance(Builder $query, EventSignificance $significance): Builder
    {
        return $query->where('significance', $significance);
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('significance', [EventSignificance::High, EventSignificance::Critical]);
    }

    /**
     * @param  Builder<BusinessEvent>  $query
     * @return Builder<BusinessEvent>
     */
    public function scopeForPeriod(Builder $query, \Carbon\Carbon $start, \Carbon\Carbon $end): Builder
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    public function isHighPriority(): bool
    {
        return in_array($this->significance, [EventSignificance::High, EventSignificance::Critical]);
    }
}
