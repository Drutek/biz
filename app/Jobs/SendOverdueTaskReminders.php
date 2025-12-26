<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\OverdueTasksNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOverdueTaskReminders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct() {}

    public function handle(): void
    {
        Log::info('Starting overdue task reminders check');

        $now = now();

        $users = User::query()
            ->whereHas('preferences', function ($query) {
                $query->where('overdue_reminders_enabled', true);
            })
            ->get();

        foreach ($users as $user) {
            try {
                $preferences = $user->preferences;

                if (! $preferences) {
                    continue;
                }

                if (! $preferences->shouldSendOverdueReminderAt($now)) {
                    continue;
                }

                $overdueTasks = Task::query()
                    ->where('user_id', $user->id)
                    ->overdue()
                    ->pending()
                    ->orderByDesc('priority')
                    ->get();

                if ($overdueTasks->isEmpty()) {
                    continue;
                }

                $user->notify(new OverdueTasksNotification($overdueTasks));

                Log::info("Overdue task reminder sent to user {$user->id}", [
                    'task_count' => $overdueTasks->count(),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send overdue task reminder to user {$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Overdue task reminders check completed');
    }
}
