<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductRevenueSnapshot;
use Illuminate\Console\Command;

class CaptureProductSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:capture-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture monthly revenue snapshots for all active products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $products = Product::active()->get();

        if ($products->isEmpty()) {
            $this->info('No active products to snapshot.');

            return self::SUCCESS;
        }

        $this->info("Capturing snapshots for {$products->count()} products...");

        $captured = 0;
        foreach ($products as $product) {
            $snapshot = ProductRevenueSnapshot::captureSnapshot($product);

            if ($snapshot->wasRecentlyCreated) {
                $this->line("  Created snapshot for: {$product->name}");
            } else {
                $this->line("  Updated snapshot for: {$product->name}");
            }

            $captured++;
        }

        $this->info("Captured {$captured} snapshots.");

        return self::SUCCESS;
    }
}
