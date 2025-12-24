<?php

namespace App\Models;

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
        ];
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
}
