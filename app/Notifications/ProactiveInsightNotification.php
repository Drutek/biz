<?php

namespace App\Notifications;

use App\Models\ProactiveInsight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProactiveInsightNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProactiveInsight $insight) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'proactive_insight',
            'insight_id' => $this->insight->id,
            'title' => $this->insight->title,
            'insight_type' => $this->insight->insight_type->value,
            'priority' => $this->insight->priority->value,
            'trigger_type' => $this->insight->trigger_type->value,
            'content_preview' => \Str::limit($this->insight->content, 100),
        ];
    }
}
