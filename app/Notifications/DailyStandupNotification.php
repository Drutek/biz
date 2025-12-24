<?php

namespace App\Notifications;

use App\Models\DailyStandup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyStandupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DailyStandup $standup) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $snapshot = $this->standup->financial_snapshot ?? [];
        $alerts = $this->standup->alerts ?? [];
        $companyName = $snapshot['company_name'] ?? 'Your Business';

        $mail = (new MailMessage)
            ->subject("{$companyName} - Daily Business Briefing")
            ->greeting('Good morning!')
            ->line("Here's your daily business briefing for ".now()->format('l, F j, Y').'.');

        if (! empty($snapshot)) {
            $mail->line('**Financial Snapshot:**');
            $mail->line('- Monthly Income: $'.number_format($snapshot['monthly_income'] ?? 0, 2));
            $mail->line('- Monthly Expenses: $'.number_format($snapshot['monthly_expenses'] ?? 0, 2));
            $mail->line('- Net Monthly: $'.number_format($snapshot['monthly_net'] ?? 0, 2));

            if (isset($snapshot['runway_months'])) {
                $runway = is_numeric($snapshot['runway_months'])
                    ? number_format($snapshot['runway_months'], 1).' months'
                    : 'Sustainable';
                $mail->line("- Runway: {$runway}");
            }
        }

        if (! empty($alerts)) {
            $mail->line('');
            $mail->line('**Alerts:**');

            if (! empty($alerts['contracts_expiring'])) {
                foreach ($alerts['contracts_expiring'] as $contract) {
                    $mail->line("- Contract '{$contract['name']}' expires in {$contract['days_remaining']} days");
                }
            }

            if (! empty($alerts['runway'])) {
                $mail->line("- Runway Alert: {$alerts['runway']['current_runway']} months remaining");
            }

            if (! empty($alerts['unread_insights'])) {
                $mail->line("- {$alerts['unread_insights']} unread AI insights");
            }
        }

        if ($this->standup->ai_summary) {
            $mail->line('');
            $mail->line('**AI Summary:**');
            $mail->line($this->standup->ai_summary);
        }

        $mail->action('View Full Briefing', url('/today'))
            ->line('Have a productive day!');

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $snapshot = $this->standup->financial_snapshot ?? [];
        $alerts = $this->standup->alerts ?? [];

        return [
            'standup_id' => $this->standup->id,
            'standup_date' => $this->standup->standup_date->toDateString(),
            'has_alerts' => ! empty($alerts),
            'alert_count' => $this->countAlerts($alerts),
            'monthly_net' => $snapshot['monthly_net'] ?? null,
            'runway_months' => $snapshot['runway_months'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $alerts
     */
    protected function countAlerts(array $alerts): int
    {
        $count = 0;

        if (! empty($alerts['contracts_expiring'])) {
            $count += count($alerts['contracts_expiring']);
        }

        if (! empty($alerts['runway'])) {
            $count++;
        }

        if (! empty($alerts['urgent_events'])) {
            $count += count($alerts['urgent_events']);
        }

        return $count;
    }
}
