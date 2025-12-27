<?php

namespace App\Services\LLM\Tools;

use App\Enums\PricingModel;
use App\Models\Product;
use App\Models\Setting;

class ProductsTool implements Tool
{
    public function name(): string
    {
        return 'get_products';
    }

    public function description(): string
    {
        return 'Get information about the user\'s products (books, SaaS apps, courses, templates, etc). Use this to see product revenue, development status, time investment, and profitability. Call this when the user asks about their products or when you need product context to give advice.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Get details for a specific product by ID. If not provided, returns a summary of all products.',
                ],
                'status_filter' => [
                    'type' => 'string',
                    'enum' => ['all', 'launched', 'in_development', 'idea'],
                    'description' => 'Optional: Filter products by status. Defaults to "all".',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $input): string
    {
        $productId = $input['product_id'] ?? null;
        $statusFilter = $input['status_filter'] ?? 'all';

        if ($productId) {
            return $this->getProductDetail($productId);
        }

        return $this->getProductsSummary($statusFilter);
    }

    private function getProductsSummary(string $statusFilter): string
    {
        $query = Product::query();

        if ($statusFilter === 'launched') {
            $query->launched();
        } elseif ($statusFilter === 'in_development') {
            $query->inDevelopment();
        } elseif ($statusFilter === 'idea') {
            $query->where('status', 'idea');
        }

        $products = $query->orderByDesc('created_at')->get();

        if ($products->isEmpty()) {
            return 'No products found.';
        }

        $hourlyRate = (float) Setting::get(Setting::KEY_HOURLY_RATE, 0);

        $lines = ["Found {$products->count()} product(s):\n"];

        foreach ($products as $product) {
            $lines[] = $this->formatProductSummary($product, $hourlyRate);
        }

        return implode("\n", $lines);
    }

    private function getProductDetail(int $productId): string
    {
        $product = Product::with(['milestones' => fn ($q) => $q->orderBy('sort_order')])
            ->find($productId);

        if (! $product) {
            return "Product with ID {$productId} not found.";
        }

        $hourlyRate = (float) Setting::get(Setting::KEY_HOURLY_RATE, 0);

        return $this->formatProductDetail($product, $hourlyRate);
    }

    private function formatProductSummary(Product $product, float $hourlyRate): string
    {
        $timeInvested = $product->hours_invested * $hourlyRate;
        $profit = $product->total_revenue - $timeInvested;
        $profitLabel = $profit >= 0 ? "+{$this->formatMoney($profit)}" : $this->formatMoney($profit);

        $revenue = $product->pricing_model === PricingModel::Subscription
            ? "{$this->formatMoney($product->mrr)}/mo MRR, {$product->subscriber_count} subscribers"
            : "{$this->formatMoney($product->total_revenue)} total, {$product->units_sold} sold";

        $lines = [
            "ID: {$product->id} | {$product->name}",
            "  Type: {$product->product_type->label()} | Status: {$product->status->label()}",
            "  Revenue: {$revenue}",
            "  Time: {$product->hours_invested}h invested ({$this->formatMoney($timeInvested)} opportunity cost)",
            "  Profit/Loss: {$profitLabel}",
        ];

        if ($product->hours_invested > 0 && $product->total_revenue > 0) {
            $effectiveRate = $product->effectiveHourlyRate();
            $comparison = $hourlyRate > 0 ? ' ('.round(($effectiveRate / $hourlyRate) * 100).'% of consulting rate)' : '';
            $lines[] = "  Effective Rate: {$this->formatMoney($effectiveRate)}/hr{$comparison}";
        }

        $trend = $product->revenueTrend();
        if ($trend !== null) {
            $trendSign = $trend >= 0 ? '+' : '';
            $lines[] = "  Trend: {$trendSign}".number_format($trend, 1).'% over last 3 months';
        }

        return implode("\n", $lines)."\n";
    }

    private function formatProductDetail(Product $product, float $hourlyRate): string
    {
        $timeInvested = $product->hours_invested * $hourlyRate;
        $profit = $product->total_revenue - $timeInvested;

        $lines = [
            "=== {$product->name} ===",
            '',
            'BASICS:',
            "  ID: {$product->id}",
            "  Type: {$product->product_type->label()}",
            "  Status: {$product->status->label()}",
            "  Pricing: {$product->pricing_model->label()}".($product->price ? " at {$this->formatMoney($product->price)}" : ''),
            '',
            'REVENUE:',
        ];

        if ($product->pricing_model === PricingModel::Subscription) {
            $lines[] = "  MRR: {$this->formatMoney($product->mrr)}";
            $lines[] = "  Subscribers: {$product->subscriber_count}";
            $lines[] = "  ARR: {$this->formatMoney($product->mrr * 12)}";
        }

        $lines[] = "  Total Revenue: {$this->formatMoney($product->total_revenue)}";
        $lines[] = "  Units Sold: {$product->units_sold}";

        $lines[] = '';
        $lines[] = 'TIME INVESTMENT:';
        $lines[] = "  Hours Invested: {$product->hours_invested}h";
        $lines[] = "  Opportunity Cost: {$this->formatMoney($timeInvested)}".($hourlyRate > 0 ? " (@ {$this->formatMoney($hourlyRate)}/hr)" : '');
        $lines[] = "  Monthly Maintenance: {$product->monthly_maintenance_hours}h/mo";

        $lines[] = '';
        $lines[] = 'PROFITABILITY:';
        $profitLabel = $profit >= 0 ? "+{$this->formatMoney($profit)}" : $this->formatMoney($profit);
        $lines[] = "  Profit/Loss: {$profitLabel}";

        if ($product->hours_invested > 0 && $product->total_revenue > 0) {
            $effectiveRate = $product->effectiveHourlyRate();
            $lines[] = "  Effective Hourly Rate: {$this->formatMoney($effectiveRate)}/hr";
            if ($hourlyRate > 0) {
                $percentage = round(($effectiveRate / $hourlyRate) * 100);
                $lines[] = "  vs Consulting Rate: {$percentage}% ".($percentage >= 100 ? '(PROFITABLE)' : '(BELOW CONSULTING)');
            }
        }

        $trend = $product->revenueTrend();
        if ($trend !== null) {
            $trendSign = $trend >= 0 ? '+' : '';
            $trendStatus = $trend >= 0 ? 'GROWING' : 'DECLINING';
            $lines[] = "  3-Month Trend: {$trendSign}".number_format($trend, 1)."% ({$trendStatus})";
        }

        $lines[] = '';
        $lines[] = 'TIMELINE:';
        if ($product->launched_at) {
            $lines[] = "  Launched: {$product->launched_at->format('M j, Y')}";
        }
        if ($product->target_launch_date) {
            $days = $product->daysToLaunch();
            $daysLabel = $days !== null ? " ({$days} days away)" : '';
            $lines[] = "  Target Launch: {$product->target_launch_date->format('M j, Y')}{$daysLabel}";
        }

        if ($product->milestones->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'MILESTONES:';
            foreach ($product->milestones as $milestone) {
                $status = $milestone->status->label();
                $target = $milestone->target_date ? " (target: {$milestone->target_date->format('M j')})" : '';
                $overdue = $milestone->isOverdue() ? ' [OVERDUE]' : '';
                $lines[] = "  - [{$status}] {$milestone->title}{$target}{$overdue}";
            }
        }

        if ($product->description) {
            $lines[] = '';
            $lines[] = 'DESCRIPTION:';
            $lines[] = "  {$product->description}";
        }

        if ($product->notes) {
            $lines[] = '';
            $lines[] = 'NOTES:';
            $lines[] = "  {$product->notes}";
        }

        if ($product->url) {
            $lines[] = '';
            $lines[] = "URL: {$product->url}";
        }

        return implode("\n", $lines);
    }

    private function formatMoney(float $amount): string
    {
        return Setting::formatCurrency($amount);
    }

    public function toClaudeFormat(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'input_schema' => $this->inputSchema(),
        ];
    }
}
