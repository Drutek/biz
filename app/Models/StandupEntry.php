<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandupEntry extends Model
{
    /** @use HasFactory<\Database\Factories\StandupEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_standup_id',
        'yesterday_accomplished',
        'today_planned',
        'blockers',
        'ai_follow_up_questions',
        'ai_follow_up_responses',
        'ai_analysis',
        'submitted_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'ai_follow_up_questions' => 'array',
            'ai_follow_up_responses' => 'array',
            'submitted_at' => 'datetime',
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
     * @return BelongsTo<DailyStandup, $this>
     */
    public function dailyStandup(): BelongsTo
    {
        return $this->belongsTo(DailyStandup::class);
    }

    public function hasFollowUpQuestions(): bool
    {
        return ! empty($this->ai_follow_up_questions);
    }

    public function hasFollowUpResponses(): bool
    {
        return ! empty($this->ai_follow_up_responses);
    }

    public function isComplete(): bool
    {
        return $this->submitted_at !== null;
    }

    public function hasBlockers(): bool
    {
        return ! empty($this->blockers);
    }
}
