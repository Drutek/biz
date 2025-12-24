<?php

namespace App\Jobs;

use App\Models\DailyStandup;
use App\Models\User;
use App\Notifications\DailyStandupNotification;
use App\Services\StandupGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchDailyStandups implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct() {}

    public function handle(StandupGenerator $generator): void
    {
        Log::info('Starting daily standup dispatch');

        $currentHour = now()->hour;
        $currentMinute = now()->minute;

        $users = User::query()
            ->whereHas('preferences', function ($query) use ($currentHour, $currentMinute) {
                $query
                    ->where('standup_email_enabled', true)
                    ->whereRaw('CAST(standup_email_time AS INTEGER) = ?', [$currentHour])
                    ->whereRaw('CAST(SUBSTR(standup_email_time, INSTR(standup_email_time, ":") + 1, 2) AS INTEGER) >= ? AND CAST(SUBSTR(standup_email_time, INSTR(standup_email_time, ":") + 1, 2) AS INTEGER) < ?', [
                        $currentMinute,
                        $currentMinute + 15,
                    ]);
            })
            ->get();

        if ($users->isEmpty()) {
            $users = $this->getUsersForCurrentTimeWindow();
        }

        foreach ($users as $user) {
            try {
                $standup = $this->getOrGenerateStandup($user, $generator);

                if (! $standup->email_sent_at) {
                    $user->notify(new DailyStandupNotification($standup));
                    $standup->markAsEmailSent();
                    Log::info("Daily standup email sent to user {$user->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to dispatch standup for user {$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily standup dispatch completed');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    protected function getUsersForCurrentTimeWindow()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $windowStart = $now->copy()->subMinutes(7)->format('H:i');
        $windowEnd = $now->copy()->addMinutes(7)->format('H:i');

        return User::query()
            ->whereHas('preferences', function ($query) use ($windowStart, $windowEnd) {
                $query->where('standup_email_enabled', true);

                if ($windowStart <= $windowEnd) {
                    $query->where('standup_email_time', '>=', $windowStart)
                        ->where('standup_email_time', '<=', $windowEnd);
                } else {
                    $query->where(function ($q) use ($windowStart, $windowEnd) {
                        $q->where('standup_email_time', '>=', $windowStart)
                            ->orWhere('standup_email_time', '<=', $windowEnd);
                    });
                }
            })
            ->get();
    }

    protected function getOrGenerateStandup(User $user, StandupGenerator $generator): DailyStandup
    {
        $existingStandup = DailyStandup::query()
            ->where('user_id', $user->id)
            ->whereDate('standup_date', now())
            ->first();

        if ($existingStandup) {
            return $existingStandup;
        }

        return $generator->generate($user);
    }
}
