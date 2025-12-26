<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class OverdueTasksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Models\Task>  $overdueTasks
     */
    public function __construct(public Collection $overdueTasks) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->overdueTasks->count();
        $taskWord = $count === 1 ? 'task' : 'tasks';

        $mail = (new MailMessage)
            ->subject("Reminder: {$count} Overdue {$taskWord}")
            ->greeting('Task Reminder')
            ->line("You have {$count} overdue {$taskWord} that need your attention:");

        foreach ($this->overdueTasks as $task) {
            $daysOverdue = $task->daysOverdue();
            $mail->line("- **{$task->title}** (due {$task->due_date->format('M j, Y')}, {$daysOverdue} days overdue)");
        }

        $mail->action('View Tasks', url('/tasks'))
            ->line('Complete or update these tasks to stay on track.');

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overdue_tasks_reminder',
            'task_count' => $this->overdueTasks->count(),
            'task_ids' => $this->overdueTasks->pluck('id')->toArray(),
            'max_days_overdue' => $this->overdueTasks->max(fn ($task) => $task->daysOverdue()),
        ];
    }
}
