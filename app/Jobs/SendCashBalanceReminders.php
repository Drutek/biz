<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\CashBalanceReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCashBalanceReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        User::query()
            ->whereHas('preferences', function ($query) {
                $query->where('in_app_notifications_enabled', true);
            })
            ->each(function (User $user) {
                $user->notify(new CashBalanceReminderNotification);
            });
    }
}
