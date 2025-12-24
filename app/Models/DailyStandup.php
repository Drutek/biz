<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStandup extends Model
{
    /** @use HasFactory<\Database\Factories\DailyStandupFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'standup_date',
        'financial_snapshot',
        'alerts',
        'ai_summary',
        'ai_insights',
        'events_count',
        'generated_at',
        'email_sent_at',
        'viewed_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'standup_date' => 'date',
            'financial_snapshot' => 'array',
            'alerts' => 'array',
            'ai_insights' => 'array',
            'events_count' => 'integer',
            'generated_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'viewed_at' => 'datetime',
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
     * @param  Builder<DailyStandup>  $query
     * @return Builder<DailyStandup>
     */
    public function scopeForDate(Builder $query, \Carbon\Carbon $date): Builder
    {
        return $query->whereDate('standup_date', $date);
    }

    /**
     * @param  Builder<DailyStandup>  $query
     * @return Builder<DailyStandup>
     */
    public function scopeUnviewed(Builder $query): Builder
    {
        return $query->whereNull('viewed_at');
    }

    /**
     * @param  Builder<DailyStandup>  $query
     * @return Builder<DailyStandup>
     */
    public function scopeEmailNotSent(Builder $query): Builder
    {
        return $query->whereNull('email_sent_at');
    }

    public function hasAlerts(): bool
    {
        return ! empty($this->alerts);
    }

    public function markAsViewed(): void
    {
        $this->update(['viewed_at' => now()]);
    }

    public function markEmailSent(): void
    {
        $this->update(['email_sent_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormattedSnapshot(): array
    {
        $snapshot = $this->financial_snapshot ?? [];

        return [
            'monthly_income' => number_format($snapshot['monthly_income'] ?? 0, 2),
            'monthly_expenses' => number_format($snapshot['monthly_expenses'] ?? 0, 2),
            'monthly_net' => number_format($snapshot['monthly_net'] ?? 0, 2),
            'runway_months' => isset($snapshot['runway_months'])
                ? (is_null($snapshot['runway_months']) ? 'Sustainable' : number_format($snapshot['runway_months'], 1).' months')
                : 'Unknown',
            'contracts_count' => $snapshot['contracts_count'] ?? 0,
            'pipeline_count' => $snapshot['pipeline_count'] ?? 0,
        ];
    }
}
