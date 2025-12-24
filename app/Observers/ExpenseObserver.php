<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\User;
use App\Services\BusinessEventRecorder;

class ExpenseObserver
{
    protected const SIGNIFICANT_CHANGE_THRESHOLD = 10; // 10% change is significant

    public function __construct(
        protected BusinessEventRecorder $recorder
    ) {}

    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        $this->recorder->recordExpenseChange($expense, $user, 'created');
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        // Only record if amount changed significantly
        if ($expense->wasChanged('amount')) {
            $oldAmount = (float) $expense->getOriginal('amount');
            $newAmount = (float) $expense->amount;

            if ($this->isSignificantChange($oldAmount, $newAmount)) {
                $changeType = $newAmount > $oldAmount ? 'increased' : 'decreased';
                $this->recorder->recordExpenseChange($expense, $user, $changeType, $oldAmount);
            }
        }
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        $this->recorder->recordExpenseChange($expense, $user, 'deleted');
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        //
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        //
    }

    protected function getUser(): ?User
    {
        return auth()->user();
    }

    protected function isSignificantChange(float $oldAmount, float $newAmount): bool
    {
        if ($oldAmount <= 0) {
            return true;
        }

        $percentChange = abs(($newAmount - $oldAmount) / $oldAmount) * 100;

        return $percentChange >= self::SIGNIFICANT_CHANGE_THRESHOLD;
    }
}
