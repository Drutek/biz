<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'standup_email_enabled',
        'standup_email_time',
        'standup_email_timezone',
        'in_app_notifications_enabled',
        'proactive_insights_enabled',
        'runway_alert_threshold',
        'hourly_rate',
        'weekends_are_workdays',
        'task_suggestions_enabled',
        'overdue_reminders_enabled',
        'overdue_reminder_time',
        'interactive_standup_enabled',
        'task_integration_provider',
        'task_integration_config',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'standup_email_enabled' => 'boolean',
            'in_app_notifications_enabled' => 'boolean',
            'proactive_insights_enabled' => 'boolean',
            'runway_alert_threshold' => 'integer',
            'hourly_rate' => 'decimal:2',
            'weekends_are_workdays' => 'boolean',
            'task_suggestions_enabled' => 'boolean',
            'overdue_reminders_enabled' => 'boolean',
            'interactive_standup_enabled' => 'boolean',
            'task_integration_config' => 'array',
        ];
    }

    public function hasTaskIntegration(): bool
    {
        return $this->task_integration_provider !== null
            && ! empty($this->task_integration_config);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTaskIntegrationConfig(): array
    {
        return $this->task_integration_config ?? [];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldSendEmailAt(\Carbon\Carbon $time): bool
    {
        if (! $this->standup_email_enabled) {
            return false;
        }

        $userTime = $time->copy()->setTimezone($this->standup_email_timezone);
        $scheduledTime = \Carbon\Carbon::parse($this->standup_email_time, $this->standup_email_timezone);

        return $userTime->format('H:i') === $scheduledTime->format('H:i');
    }

    public function isWithinAlertThreshold(float $runwayMonths): bool
    {
        if (is_infinite($runwayMonths)) {
            return false;
        }

        return $runwayMonths <= $this->runway_alert_threshold;
    }

    public function isWorkDay(?Carbon $date = null): bool
    {
        $date = $date ?? now();
        $dayOfWeek = $date->dayOfWeek;

        // Saturday = 6, Sunday = 0
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        return ! $isWeekend || $this->weekends_are_workdays;
    }

    public function shouldSendOverdueReminderAt(Carbon $time): bool
    {
        if (! $this->overdue_reminders_enabled) {
            return false;
        }

        if (! $this->isWorkDay($time)) {
            return false;
        }

        $userTime = $time->copy()->setTimezone($this->standup_email_timezone);
        $scheduledTime = Carbon::parse($this->overdue_reminder_time, $this->standup_email_timezone);

        return $userTime->format('H:i') === $scheduledTime->format('H:i');
    }
}
