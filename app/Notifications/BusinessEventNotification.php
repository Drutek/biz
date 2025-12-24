<?php

namespace App\Notifications;

use App\Models\BusinessEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BusinessEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessEvent $event) {}

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
            'type' => 'business_event',
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'description' => $this->event->description,
            'event_type' => $this->event->event_type->value,
            'category' => $this->event->category->value,
            'significance' => $this->event->significance->value,
            'occurred_at' => $this->event->occurred_at->toIso8601String(),
        ];
    }
}
