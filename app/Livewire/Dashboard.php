<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\NewsItem;
use App\Services\CashflowCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();
        $projections = $calculator->project(12);

        $upcomingEndDates = Contract::query()
            ->confirmed()
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addMonths(3))
            ->orderBy('end_date')
            ->limit(5)
            ->get();

        $recentNews = NewsItem::query()
            ->with('trackedEntity')
            ->relevant()
            ->recent()
            ->orderByDesc('fetched_at')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'summary' => $summary,
            'projections' => $projections,
            'upcomingEndDates' => $upcomingEndDates,
            'recentNews' => $recentNews,
        ]);
    }

    public function formatCurrency(float $value): string
    {
        if ($value >= 1000000) {
            return number_format($value / 1000000, 1).'M';
        }

        if ($value >= 1000) {
            return number_format($value / 1000, 1).'K';
        }

        return number_format($value, 0);
    }

    public function formatRunway(float $months): string
    {
        if ($months === INF) {
            return 'Sustainable';
        }

        return number_format($months, 1).' months';
    }
}
