<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\ExpenseFrequency;
use App\Enums\PricingModel;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\NewsItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Embedding\VectorSearchService;
use Illuminate\Support\Str;

class AdvisoryContextBuilder
{
    public function __construct(
        protected ?VectorSearchService $vectorSearch = null
    ) {}

    public function build(?User $user = null): string
    {
        $companyName = Setting::get(Setting::KEY_COMPANY_NAME, 'Your Business');
        $businessProfile = $this->formatBusinessProfile();
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();
        $projections = $calculator->project(6);

        $eventHistory = $user ? $this->formatEventHistory($user) : '';

        $context = <<<EOT
You are a strategic business advisor for {$companyName}. You have access to their current financial position and recent market news. You can use the get_products tool to see details about their product portfolio (books, SaaS, courses, etc.) when relevant.
{$businessProfile}
FINANCIAL POSITION AS OF {$this->formatDate(now())}:

Cash Balance: {$this->formatCurrency($summary['cash_balance'])}

Confirmed Monthly Income: {$this->formatCurrency($summary['monthly_income'])}
{$this->formatContracts(ContractStatus::Confirmed)}

Pipeline (Weighted): {$this->formatCurrency($summary['monthly_pipeline'])}
{$this->formatContracts(ContractStatus::Pipeline)}

Monthly Recurring Expenses: {$this->formatCurrency($summary['monthly_expenses'])}
{$this->formatExpensesByCategory()}
{$this->formatRecentOneTimeExpenses()}
Net Monthly: {$this->formatCurrency($summary['monthly_net'])}
Runway: {$this->formatRunway($summary['runway_months'])}

CASHFLOW NEXT 6 MONTHS:
{$this->formatProjections($projections)}

RECENT MARKET NEWS:
{$this->formatRecentNews()}
{$eventHistory}
---

ANALYSIS GUIDELINES:
1. Financial health is determined by RECURRING income vs RECURRING expenses, not by total cash balance
2. One-time expenses (listed under "RECENT ONE-TIME EXPENSES") are normal business costs that have already been paid - they should NOT be included in burn rate calculations
3. Tax payments (corporation tax, VAT, PAYE) are predictable annual/quarterly expenses - a cash drop after a tax payment is normal, not a crisis
4. When assessing runway, use: Cash Balance / (Monthly Recurring Expenses - Monthly Recurring Income)
5. A business is healthy if recurring income exceeds or closely matches recurring expenses, even if cash temporarily decreased from one-time payments
6. Only flag genuine concerns: sustained negative recurring cashflow, approaching contract expirations with no pipeline, or truly unusual expense patterns

Provide strategic advice based on this context. Be direct, practical, and specific. Flag risks proactively. When discussing opportunities, consider the financial constraints shown above. For product-related questions, use the get_products tool to fetch current product data, then consider ROI vs consulting rate and recommend when to continue or sunset products.
EOT;

        return $context;
    }

    /**
     * Build context with RAG-enhanced historical data.
     *
     * This method retrieves semantically relevant past conversations,
     * events, and insights to provide better context for the LLM.
     */
    public function buildWithRAG(?User $user = null, ?string $currentQuery = null): string
    {
        $baseContext = $this->build($user);

        if (! $user || ! $currentQuery || ! $this->vectorSearch) {
            return $baseContext;
        }

        $relevantHistory = $this->getRelevantHistory($user, $currentQuery);

        if (empty($relevantHistory)) {
            return $baseContext;
        }

        return $baseContext."\n\nRELEVANT HISTORICAL CONTEXT:\n".$relevantHistory;
    }

    /**
     * Retrieve semantically relevant historical data for the given query.
     */
    protected function getRelevantHistory(User $user, string $query): string
    {
        if (! $this->vectorSearch) {
            return '';
        }

        $context = $this->vectorSearch->getRelevantContext(
            userId: $user->id,
            query: $query,
            messagesLimit: 5,
            eventsLimit: 3,
            insightsLimit: 3
        );

        $sections = [];

        if ($context['messages']->isNotEmpty()) {
            $sections[] = $this->formatRelevantMessages($context['messages']);
        }

        if ($context['events']->isNotEmpty()) {
            $sections[] = $this->formatRelevantEvents($context['events']);
        }

        if ($context['insights']->isNotEmpty()) {
            $sections[] = $this->formatRelevantInsights($context['insights']);
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $messages
     */
    protected function formatRelevantMessages(\Illuminate\Support\Collection $messages): string
    {
        $lines = ['Similar Past Conversations:'];

        foreach ($messages as $message) {
            $date = \Carbon\Carbon::parse($message->created_at)->format('M j, Y');
            $role = ucfirst($message->role);
            $preview = Str::limit($message->content, 200);
            $lines[] = "  [{$date}] [{$role}] {$preview}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $events
     */
    protected function formatRelevantEvents(\Illuminate\Support\Collection $events): string
    {
        $lines = ['Similar Past Events:'];

        foreach ($events as $event) {
            $date = \Carbon\Carbon::parse($event->occurred_at)->format('M j, Y');
            $lines[] = "  [{$date}] {$event->title}";
            if ($event->description) {
                $lines[] = '    '.Str::limit($event->description, 150);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $insights
     */
    protected function formatRelevantInsights(\Illuminate\Support\Collection $insights): string
    {
        $lines = ['Related Past Insights:'];

        foreach ($insights as $insight) {
            $date = \Carbon\Carbon::parse($insight->created_at)->format('M j, Y');
            $lines[] = "  [{$date}] {$insight->title}";
            $lines[] = '    '.Str::limit($insight->content, 150);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();

        $runwayMonths = $summary['runway_months'];

        // Product metrics
        $launchedProducts = Product::launched()->get();
        $productMrr = $launchedProducts->sum('mrr');
        $productTotalRevenue = $launchedProducts->sum('total_revenue');

        return [
            'company_name' => Setting::get(Setting::KEY_COMPANY_NAME, 'Your Business'),
            'business_industry' => Setting::get(Setting::KEY_BUSINESS_INDUSTRY, ''),
            'business_description' => Setting::get(Setting::KEY_BUSINESS_DESCRIPTION, ''),
            'cash_balance' => $summary['cash_balance'],
            'monthly_income' => $summary['monthly_income'],
            'monthly_expenses' => $summary['monthly_expenses'],
            'monthly_pipeline' => $summary['monthly_pipeline'],
            'monthly_net' => $summary['monthly_net'],
            'runway_months' => is_infinite($runwayMonths) ? null : $runwayMonths,
            'contracts_count' => Contract::confirmed()->count(),
            'pipeline_count' => Contract::pipeline()->count(),
            'products_launched_count' => $launchedProducts->count(),
            'products_in_development_count' => Product::inDevelopment()->count(),
            'product_mrr' => $productMrr,
            'product_total_revenue' => $productTotalRevenue,
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

    private function formatRecentOneTimeExpenses(): string
    {
        $oneTimeExpenses = Expense::query()
            ->where('frequency', ExpenseFrequency::OneTime)
            ->where('start_date', '>=', now()->subDays(90))
            ->orderByDesc('start_date')
            ->limit(10)
            ->get();

        if ($oneTimeExpenses->isEmpty()) {
            return '';
        }

        $lines = ["\nRECENT ONE-TIME EXPENSES (not included in monthly burn):"];
        foreach ($oneTimeExpenses as $expense) {
            $date = $expense->start_date->format('M j, Y');
            $category = ucfirst($expense->category);
            $lines[] = "  - [{$date}] {$expense->name} ({$category}): {$this->formatCurrency($expense->amount)}";
        }
        $lines[] = '  Note: These are one-time payments that have already occurred and should NOT be treated as ongoing operational expenses.';

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

    private function formatProductSummary(?User $user): string
    {
        $products = Product::active()->get();

        if ($products->isEmpty()) {
            return '';
        }

        // Get user's hourly rate for ROI comparison
        $hourlyRate = Setting::get(Setting::KEY_HOURLY_RATE);
        $hourlyRate = $hourlyRate ? (float) $hourlyRate : null;

        $lines = ["\nPRODUCT PORTFOLIO:"];

        // Launched products
        $launched = $products->filter(fn (Product $p) => $p->isLaunched());
        if ($launched->isNotEmpty()) {
            $lines[] = 'Launched Products:';
            foreach ($launched as $product) {
                $lines[] = $this->formatProductLine($product, $hourlyRate);
            }
        }

        // In development products
        $inDev = $products->filter(fn (Product $p) => $p->isInDevelopment());
        if ($inDev->isNotEmpty()) {
            $lines[] = 'In Development:';
            foreach ($inDev as $product) {
                $launchInfo = $product->target_launch_date
                    ? "target: {$product->target_launch_date->format('M j, Y')}"
                    : 'no target date';
                $hoursInfo = $product->hours_invested > 0
                    ? " | {$product->hours_invested} hrs invested"
                    : '';
                $lines[] = "  - {$product->name} ({$product->status->label()}) - {$launchInfo}{$hoursInfo}";
            }
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function formatProductLine(Product $product, ?float $hourlyRate): string
    {
        $typeLabel = $product->product_type->label();

        // Revenue info based on pricing model
        if ($product->pricing_model === PricingModel::Subscription) {
            $revenueInfo = "\${$this->formatCurrency($product->mrr)} MRR, {$product->subscriber_count} subs";
        } else {
            $revenueInfo = "\${$this->formatCurrency($product->total_revenue)} total, {$product->units_sold} sold";
        }

        // Time investment
        $hoursInfo = "{$product->hours_invested} hrs invested";
        if ($product->monthly_maintenance_hours > 0) {
            $hoursInfo .= ", {$product->monthly_maintenance_hours} hrs/mo maintenance";
        }

        $line = "  - {$product->name} ({$typeLabel}): {$revenueInfo} | {$hoursInfo}";

        // Calculate and show effective hourly rate
        $effectiveRate = $product->effectiveHourlyRate();
        if ($effectiveRate > 0) {
            $rateComparison = '';
            if ($hourlyRate && $hourlyRate > 0) {
                $percentage = round(($effectiveRate / $hourlyRate) * 100);
                $rateComparison = " ({$percentage}% of consulting rate)";
            }
            $line .= "\n    → Effective rate: \${$this->formatCurrency($effectiveRate)}/hr{$rateComparison}";
        }

        // Show revenue trend if available
        $trend = $product->revenueTrend();
        if ($trend !== null) {
            $trendSign = $trend >= 0 ? '+' : '';
            $trendLabel = $trend >= 0 ? 'growing' : 'declining';
            $line .= "\n    → Trend: {$trendSign}".number_format($trend, 1)."% last 3 months ({$trendLabel})";
        }

        return $line;
    }
}
