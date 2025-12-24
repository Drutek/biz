<?php

namespace App\Listeners;

use App\Enums\EventSignificance;
use App\Events\BusinessEventRecorded;
use App\Services\ProactiveAdvisor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateInsightOnEvent implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(protected ProactiveAdvisor $advisor) {}

    public function handle(BusinessEventRecorded $event): void
    {
        $businessEvent = $event->businessEvent;

        if ($businessEvent->significance === EventSignificance::Low) {
            return;
        }

        $user = $businessEvent->user;
        $preferences = $user->preferences ?? $user->getOrCreatePreferences();

        if (! $preferences->proactive_insights_enabled) {
            Log::info('Skipping insight generation - user has disabled proactive insights', [
                'user_id' => $user->id,
                'event_id' => $businessEvent->id,
            ]);

            return;
        }

        Log::info('Generating insight for business event', [
            'event_id' => $businessEvent->id,
            'event_type' => $businessEvent->event_type->value,
            'significance' => $businessEvent->significance->value,
        ]);

        $insight = $this->advisor->analyzeEvent($businessEvent);

        if ($insight) {
            Log::info('Insight generated successfully', [
                'insight_id' => $insight->id,
                'event_id' => $businessEvent->id,
            ]);
        }
    }

    public function shouldQueue(BusinessEventRecorded $event): bool
    {
        return $event->businessEvent->significance !== EventSignificance::Low;
    }
}
