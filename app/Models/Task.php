<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'proactive_insight_id',
        'daily_standup_id',
        'title',
        'description',
        'status',
        'priority',
        'source',
        'due_date',
        'suggested_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'rejected_at',
        'rejection_reason',
        'completion_notes',
        'metadata',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'source' => TaskSource::class,
            'due_date' => 'date',
            'suggested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<ProactiveInsight, $this>
     */
    public function proactiveInsight(): BelongsTo
    {
        return $this->belongsTo(ProactiveInsight::class);
    }

    /**
     * @return BelongsTo<DailyStandup, $this>
     */
    public function dailyStandup(): BelongsTo
    {
        return $this->belongsTo(DailyStandup::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeSuggested(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Suggested);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Accepted);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::InProgress);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::Completed);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Accepted, TaskStatus::InProgress]);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TaskStatus::Suggested,
            TaskStatus::Accepted,
            TaskStatus::InProgress,
        ]);
    }

    public function accept(): void
    {
        $this->update([
            'status' => TaskStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => TaskStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function start(): void
    {
        $this->update([
            'status' => TaskStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function complete(?string $notes = null): void
    {
        $this->update([
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => TaskStatus::Cancelled,
        ]);
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast() && $this->status->isPending();
    }

    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now());
    }
}
