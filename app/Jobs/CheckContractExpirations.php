<?php

namespace App\Jobs;

use App\Enums\ContractStatus;
use App\Enums\EventType;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\User;
use App\Services\BusinessEventRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckContractExpirations implements ShouldQueue
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
     * Warning thresholds in days.
     *
     * @var array<int>
     */
    protected array $warningDays = [30, 14, 7, 1];

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
        Log::info('Starting contract expiration check');

        // Get contracts with upcoming end dates at warning thresholds
        $contracts = Contract::query()
            ->whereIn('status', [ContractStatus::Confirmed, ContractStatus::Pipeline])
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->get();

        // Get users to notify (all users get contract notifications)
        $users = User::all();

        foreach ($contracts as $contract) {
            // Calculate days until end using date-only comparison
            $daysUntilEnd = (int) now()->startOfDay()->diffInDays($contract->end_date->startOfDay(), false);

            // Check if this matches one of our warning thresholds
            if (in_array($daysUntilEnd, $this->warningDays)) {
                foreach ($users as $user) {
                    // Check if we already created an event for this contract at this threshold
                    if (! $this->hasRecentExpirationEvent($user, $contract, $daysUntilEnd)) {
                        $recorder->recordContractEnding($contract, $user, $daysUntilEnd);
                        Log::info("Created expiration warning for contract {$contract->id}: {$daysUntilEnd} days for user {$user->id}");
                    }
                }
            }
        }

        Log::info('Contract expiration check completed');
    }

    protected function hasRecentExpirationEvent(User $user, Contract $contract, int $daysUntilEnd): bool
    {
        return BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('event_type', EventType::ContractEnding)
            ->where('eventable_type', Contract::class)
            ->where('eventable_id', $contract->id)
            ->where('occurred_at', '>=', now()->subDay())
            ->whereJsonContains('metadata->days_until_end', $daysUntilEnd)
            ->exists();
    }
}
