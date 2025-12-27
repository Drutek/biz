<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductRevenueSnapshot;
use App\Models\User;
use App\Services\BusinessEventRecorder;

class ProductObserver
{
    public function __construct(
        protected BusinessEventRecorder $recorder
    ) {}

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        if ($product->status === ProductStatus::Launched) {
            $this->recorder->recordProductLaunched($product, $user);
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $user = $this->getUser();
        if (! $user) {
            return;
        }

        // Check if status changed
        if ($product->wasChanged('status')) {
            $oldStatus = $product->getOriginal('status');
            $newStatus = $product->status;

            // Any status -> Launched = product launched
            if ($newStatus === ProductStatus::Launched && $oldStatus !== ProductStatus::Launched) {
                $this->recorder->recordProductLaunched($product, $user);
            } else {
                // Record other status changes
                $this->recorder->recordProductStatusChange($product, $oldStatus, $newStatus, $user);
            }
        }

        // Check if revenue metrics changed significantly
        if ($this->hasSignificantRevenueChange($product)) {
            $this->recorder->recordProductRevenueChange($product, $user);

            // Capture a snapshot when revenue changes
            ProductRevenueSnapshot::captureSnapshot($product);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Optionally record product deletion
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }

    protected function getUser(): ?User
    {
        return auth()->user();
    }

    protected function hasSignificantRevenueChange(Product $product): bool
    {
        // Check if MRR changed significantly (> 10%)
        if ($product->wasChanged('mrr')) {
            $oldMrr = (float) $product->getOriginal('mrr');
            $newMrr = (float) $product->mrr;

            if ($oldMrr > 0 && abs($newMrr - $oldMrr) / $oldMrr > 0.10) {
                return true;
            }

            // Also flag if MRR was 0 and is now significant (> $100)
            if ($oldMrr == 0 && $newMrr >= 100) {
                return true;
            }
        }

        // Check if total_revenue changed significantly (> 20%)
        if ($product->wasChanged('total_revenue')) {
            $oldRevenue = (float) $product->getOriginal('total_revenue');
            $newRevenue = (float) $product->total_revenue;

            if ($oldRevenue > 0 && abs($newRevenue - $oldRevenue) / $oldRevenue > 0.20) {
                return true;
            }

            // Also flag if revenue was 0 and is now significant (> $500)
            if ($oldRevenue == 0 && $newRevenue >= 500) {
                return true;
            }
        }

        return false;
    }
}
