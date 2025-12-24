<?php

namespace App\Observers;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;
use App\Services\BusinessEventRecorder;

class ContractObserver
{
    public function __construct(
        protected BusinessEventRecorder $recorder
    ) {}

    /**
     * Handle the Contract "created" event.
     */
    public function created(Contract $contract): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        if ($contract->status === ContractStatus::Confirmed) {
            $this->recorder->recordContractSigned($contract, $user);
        }
    }

    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        // Check if status changed
        if ($contract->wasChanged('status')) {
            $oldStatus = $contract->getOriginal('status');
            $newStatus = $contract->status;

            // Pipeline -> Confirmed = signed
            if ($oldStatus === ContractStatus::Pipeline && $newStatus === ContractStatus::Confirmed) {
                $this->recorder->recordContractSigned($contract, $user);
            }

            // Any status -> Completed = expired/ended
            if ($newStatus === ContractStatus::Completed && $oldStatus !== ContractStatus::Completed) {
                $this->recorder->recordContractExpired($contract, $user);
            }
        }

        // Check if end_date was extended (renewal)
        if ($contract->wasChanged('end_date') && $contract->status === ContractStatus::Confirmed) {
            $oldEndDate = $contract->getOriginal('end_date');
            $newEndDate = $contract->end_date;

            // If the end date was extended, it's a renewal
            if ($oldEndDate && $newEndDate && $newEndDate->isAfter($oldEndDate)) {
                $this->recorder->recordContractRenewed($contract, $user);
            }
        }
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        // Optionally record contract deletion as an event
        // For now, we don't track deletions as business events
    }

    /**
     * Handle the Contract "restored" event.
     */
    public function restored(Contract $contract): void
    {
        //
    }

    /**
     * Handle the Contract "force deleted" event.
     */
    public function forceDeleted(Contract $contract): void
    {
        //
    }

    protected function getUser(): ?User
    {
        return auth()->user();
    }
}
