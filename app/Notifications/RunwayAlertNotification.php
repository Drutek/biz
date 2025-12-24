<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RunwayAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $alertData
     */
    public function __construct(public array $alertData) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->alertData['crossed_below'] ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $currentRunway = $this->alertData['current_runway'] ?? 0;
        $threshold = $this->alertData['threshold'] ?? 3;

        return (new MailMessage)
            ->subject('Runway Alert - Immediate Attention Required')
            ->greeting('Urgent: Runway Alert')
            ->line("Your business runway has dropped to {$currentRunway} months, below your {$threshold} month warning threshold.")
            ->line('This requires immediate attention to ensure business continuity.')
            ->action('View Financial Dashboard', url('/dashboard'))
            ->line('Please review your financial position and take appropriate action.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $currentRunway = $this->alertData['current_runway'] ?? 0;
        $threshold = $this->alertData['threshold'] ?? 3;

        return [
            'type' => 'runway_alert',
            'title' => 'Runway Alert',
            'description' => "Your runway is at {$currentRunway} months (threshold: {$threshold} months)",
            'current_runway' => $currentRunway,
            'threshold' => $threshold,
            'crossed_below' => $this->alertData['crossed_below'] ?? true,
            'monthly_burn' => $this->alertData['monthly_burn'] ?? null,
            'monthly_income' => $this->alertData['monthly_income'] ?? null,
        ];
    }
}
