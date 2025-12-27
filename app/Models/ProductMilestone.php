<?php

namespace App\Models;

use App\Enums\MilestoneStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMilestone extends Model
{
    /** @use HasFactory<\Database\Factories\ProductMilestoneFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'status',
        'target_date',
        'completed_at',
        'sort_order',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => MilestoneStatus::class,
            'target_date' => 'date',
            'completed_at' => 'date',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOverdue(): bool
    {
        if ($this->status === MilestoneStatus::Completed) {
            return false;
        }

        return $this->target_date && $this->target_date->isPast();
    }

    public function markComplete(): void
    {
        $this->update([
            'status' => MilestoneStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  Builder<ProductMilestone>  $query
     * @return Builder<ProductMilestone>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            MilestoneStatus::NotStarted,
            MilestoneStatus::InProgress,
            MilestoneStatus::Blocked,
        ]);
    }

    /**
     * @param  Builder<ProductMilestone>  $query
     * @return Builder<ProductMilestone>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', MilestoneStatus::Completed);
    }

    /**
     * @param  Builder<ProductMilestone>  $query
     * @return Builder<ProductMilestone>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', MilestoneStatus::Completed)
            ->whereNotNull('target_date')
            ->where('target_date', '<', now());
    }
}
