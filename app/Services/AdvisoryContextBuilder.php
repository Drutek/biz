<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\NewsItem;
use App\Models\Setting;
use App\Models\User;

class AdvisoryContextBuilder
{
    public function build(?User $user = null): string
    {
        $companyName = Setting::get(Setting::KEY_COMPANY_NAME, 'Your Business');
        $businessProfile = $this->formatBusinessProfile();
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();
        $projections = $calculator->project(6);

        $eventHistory = $user ? $this->formatEventHistory($user) : '';

        $context = <<<EOT
You are a strategic business advisor for {$companyName}. You have access to their current financial position and recent market news.
{$businessProfile}
FINANCIAL POSITION AS OF {$this->formatDate(now())}:

Confirmed Monthly Income: {$this->formatCurrency($summary['monthly_income'])}
{$this->formatContracts(ContractStatus::Confirmed)}

Pipeline (Weighted): {$this->formatCurrency($summary['monthly_pipeline'])}
{$this->formatContracts(ContractStatus::Pipeline)}

Monthly Expenses: {$this->formatCurrency($summary['monthly_expenses'])}
{$this->formatExpensesByCategory()}

Net Monthly: {$this->formatCurrency($summary['monthly_net'])}
Runway: {$this->formatRunway($summary['runway_months'])}

CASHFLOW NEXT 6 MONTHS:
{$this->formatProjections($projections)}

RECENT MARKET NEWS:
{$this->formatRecentNews()}
{$eventHistory}
---

Provide strategic advice based on this context. Be direct, practical, and specific. Flag risks proactively. When discussing opportunities, consider the financial constraints shown above.
EOT;

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();

        $runwayMonths = $summary['runway_months'];

        return [
            'company_name' => Setting::get(Setting::KEY_COMPANY_NAME, 'Your Business'),
            'business_industry' => Setting::get(Setting::KEY_BUSINESS_INDUSTRY, ''),
            'business_description' => Setting::get(Setting::KEY_BUSINESS_DESCRIPTION, ''),
            'monthly_income' => $summary['monthly_income'],
            'monthly_expenses' => $summary['monthly_expenses'],
            'monthly_pipeline' => $summary['monthly_pipeline'],
            'monthly_net' => $summary['monthly_net'],
            'runway_months' => is_infinite($runwayMonths) ? null : $runwayMonths,
            'contracts_count' => Contract::confirmed()->count(),
            'pipeline_count' => Contract::pipeline()->count(),
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function formatBusinessProfile(): string
    {
        $industry = Setting::get(Setting::KEY_BUSINESS_INDUSTRY, '');
        $description = Setting::get(Setting::KEY_BUSINESS_DESCRIPTION, '');
        $targetMarket = Setting::get(Setting::KEY_BUSINESS_TARGET_MARKET, '');
        $keyServices = Setting::get(Setting::KEY_BUSINESS_KEY_SERVICES, '');

        if (empty($industry) && empty($description) && empty($targetMarket) && empty($keyServices)) {
            return '';
        }

        $lines = ["\nBUSINESS PROFILE:"];

        if (! empty($industry)) {
            $lines[] = "Industry: {$industry}";
        }

        if (! empty($description)) {
            $lines[] = "Description: {$description}";
        }

        if (! empty($targetMarket)) {
            $lines[] = "Target Market: {$targetMarket}";
        }

        if (! empty($keyServices)) {
            $lines[] = "Key Services: {$keyServices}";
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function formatDate(\Carbon\Carbon $date): string
    {
        return $date->format('F j, Y');
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2);
    }

    private function formatRunway(float $months): string
    {
        if ($months === INF) {
            return 'Sustainable (income exceeds expenses)';
        }

        return number_format($months, 1).' months';
    }

    private function formatContracts(ContractStatus $status): string
    {
        $contracts = Contract::query()
            ->where('status', $status)
            ->active()
            ->get();

        if ($contracts->isEmpty()) {
            return '  - None';
        }

        $lines = [];
        foreach ($contracts as $contract) {
            $value = $this->formatCurrency($contract->monthlyValue());
            $endDate = $contract->end_date ? ' (ends '.$contract->end_date->format('M j, Y').')' : ' (ongoing)';
            $probability = $status === ContractStatus::Pipeline ? " ({$contract->probability}%)" : '';
            $lines[] = "  - {$contract->name}: {$value}/mo{$probability}{$endDate}";
        }

        return implode("\n", $lines);
    }

    private function formatExpensesByCategory(): string
    {
        $expenses = Expense::active()->recurring()->get();

        if ($expenses->isEmpty()) {
            return '  - None';
        }

        $byCategory = $expenses->groupBy('category');

        $lines = [];
        foreach ($byCategory as $category => $items) {
            $total = $items->sum(fn (Expense $e) => $e->monthlyAmount());
            $lines[] = '  - '.ucfirst($category).': '.$this->formatCurrency($total).'/mo';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{month: string, income: float, expenses: float, net: float, cumulative: float}>  $projections
     */
    private function formatProjections(array $projections): string
    {
        $lines = [];
        foreach ($projections as $p) {
            $net = $p['net'] >= 0 ? '+' : '';
            $lines[] = "  {$p['month']}: Income {$this->formatCurrency($p['income'])} | Expenses {$this->formatCurrency($p['expenses'])} | Net {$net}{$this->formatCurrency($p['net'])}";
        }

        return implode("\n", $lines);
    }

    private function formatRecentNews(): string
    {
        $news = NewsItem::query()
            ->with('trackedEntity')
            ->relevant()
            ->recent(7)
            ->orderByDesc('fetched_at')
            ->limit(20)
            ->get();

        if ($news->isEmpty()) {
            return '  No recent news available.';
        }

        $lines = [];
        foreach ($news as $item) {
            $entity = $item->trackedEntity->name;
            $lines[] = "  - [{$entity}] {$item->title} ({$item->source})";
        }

        return implode("\n", $lines);
    }

    private function formatEventHistory(User $user, int $days = 30): string
    {
        $events = BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->orderByDesc('occurred_at')
            ->limit(30)
            ->get();

        if ($events->isEmpty()) {
            return '';
        }

        $lines = ["\nRECENT BUSINESS EVENTS (past {$days} days):"];
        foreach ($events as $event) {
            $date = $event->occurred_at->format('M j');
            $significance = strtoupper($event->significance->value);
            $category = ucfirst($event->category->value);
            $lines[] = "  [{$date}] [{$significance}] [{$category}] {$event->title}";
            if ($event->description) {
                $lines[] = "    {$event->description}";
            }
        }

        return implode("\n", $lines)."\n";
    }
}
