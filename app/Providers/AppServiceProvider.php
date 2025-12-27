<?php

namespace App\Providers;

use App\Events\BusinessEventRecorded;
use App\Listeners\GenerateInsightOnEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Product;
use App\Observers\ContractObserver;
use App\Observers\ExpenseObserver;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Contract::observe(ContractObserver::class);
        Expense::observe(ExpenseObserver::class);
        Product::observe(ProductObserver::class);

        Event::listen(
            BusinessEventRecorded::class,
            GenerateInsightOnEvent::class
        );
    }
}
