<?php

namespace App\Jobs;

use App\Enums\EventType;
use App\Models\BusinessEvent;
use App\Models\User;
use App\Services\BusinessEventRecorder;
use App\Services\CashflowCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckRunwayThresholds implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BusinessEventRecorder $recorder): void
    {
        Log::info('Starting runway threshold check');

        $users = User::all();

        foreach ($users as $user) {
            $this->checkRunwayForUser($user, $recorder);
        }

        Log::info('Runway threshold check completed');
    }

    protected function checkRunwayForUser(User $user, BusinessEventRecorder $recorder): void
    {
        $preferences = $user->getOrCreatePreferences();

        if (! $preferences->proactive_insights_enabled) {
            return;
        }

        $threshold = $preferences->runway_alert_threshold;
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();
        $currentRunway = $summary['runway_months'];

        // Skip if runway is infinite (sustainable)
        if (is_infinite($currentRunway)) {
            return;
        }

        $isBelow = $currentRunway <= $threshold;
        $lastEvent = $this->getLastRunwayEvent($user);

        // Determine if we should create an event
        if ($isBelow) {
            // Only create event if we haven't already warned about being below threshold today
            if (! $lastEvent || ! $this->wasRecentCrossBelow($lastEvent)) {
                $recorder->recordRunwayThreshold($user, $currentRunway, $threshold, true);
                Log::info("Created runway alert for user {$user->id}: {$currentRunway} months (threshold: {$threshold})");
            }
        } else {
            // Check if we were previously below and now recovered
            if ($lastEvent && $this->wasPreviouslyBelow($lastEvent) && ! $this->hasRecentRecoveryEvent($user)) {
                $recorder->recordRunwayThreshold($user, $currentRunway, $threshold, false);
                Log::info("Created runway recovery event for user {$user->id}: {$currentRunway} months");
            }
        }
    }

    protected function getLastRunwayEvent(User $user): ?BusinessEvent
    {
        return BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('event_type', EventType::RunwayThreshold)
            ->orderByDesc('occurred_at')
            ->first();
    }

    protected function wasRecentCrossBelow(?BusinessEvent $event): bool
    {
        if (! $event) {
            return false;
        }

        $crossedBelow = $event->metadata['crossed_below'] ?? false;

        return $crossedBelow && $event->occurred_at->isToday();
    }

    protected function wasPreviouslyBelow(?BusinessEvent $event): bool
    {
        if (! $event) {
            return false;
        }

        return $event->metadata['crossed_below'] ?? false;
    }

    protected function hasRecentRecoveryEvent(User $user): bool
    {
        return BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('event_type', EventType::RunwayThreshold)
            ->where('occurred_at', '>=', now()->subDay())
            ->whereJsonContains('metadata->crossed_below', false)
            ->exists();
    }
}
