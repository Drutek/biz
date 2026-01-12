<?php

namespace App\Services;

use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Enums\TriggerType;
use App\Models\BusinessEvent;
use App\Models\ProactiveInsight;
use App\Models\User;
use App\Services\Embedding\VectorSearchService;
use App\Services\LLM\LLMManager;
use App\Services\LLM\LLMResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProactiveAdvisor
{
    public function __construct(
        protected LLMManager $llmManager,
        protected AdvisoryContextBuilder $contextBuilder,
        protected ?VectorSearchService $vectorSearch = null
    ) {}

    public function generateDailyAnalysis(User $user): ?ProactiveInsight
    {
        $context = $this->contextBuilder->build();
        $eventHistory = $this->formatRecentEvents($user, 7);

        $prompt = <<<EOT
Based on the current financial position and recent business events, provide a brief daily analysis and any urgent recommendations.

{$eventHistory}

Focus on:
1. Any immediate risks or concerns
2. Opportunities worth acting on today
3. Key metrics that need attention

IMPORTANT GUIDELINES:
- Check the "RECENT ONE-TIME EXPENSES" section before concluding there's a cash crisis
- One-time expenses (tax payments, equipment purchases, annual subscriptions) are NORMAL business operations, not emergencies
- Only flag cash concerns if the RECURRING monthly burn rate exceeds income, not if cash dropped due to expected one-time payments
- Corporation tax, VAT, equipment purchases, and similar one-time payments should NOT trigger crisis alerts
- If cash decreased but there's a matching one-time expense, that explains the decrease - it's not a mystery or crisis

Be concise and actionable. If there are no urgent matters, provide a brief summary of the current position.
EOT;

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            return $this->createInsight(
                user: $user,
                response: $response,
                triggerType: TriggerType::Scheduled,
                triggerContext: ['type' => 'daily_analysis', 'date' => now()->toDateString()],
                insightType: InsightType::Analysis,
                title: 'Daily Business Analysis',
                priority: $this->determinePriorityFromContent($response->content)
            );
        } catch (\Exception $e) {
            Log::error('Failed to generate daily analysis', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function generateWeeklyAnalysis(User $user): ?ProactiveInsight
    {
        $context = $this->contextBuilder->build();
        $eventHistory = $this->formatRecentEvents($user, 14);

        $prompt = <<<EOT
Provide a strategic weekly review of this business based on the current financial position and recent business events.

{$eventHistory}

Include:
1. Week in review - key events and their impact
2. Financial health assessment
3. Strategic opportunities to pursue
4. Risks to monitor
5. Recommended priorities for the coming week

IMPORTANT GUIDELINES FOR FINANCIAL ASSESSMENT:
- Check the "RECENT ONE-TIME EXPENSES" section to understand cash balance changes
- One-time expenses (tax payments, equipment, annual subscriptions) are planned business costs, not emergencies
- Assess financial health based on RECURRING income vs RECURRING expenses, not on cash fluctuations from one-time payments
- Corporation tax, VAT, equipment purchases are normal and expected - do not treat them as crises
- When calculating "burn rate", only use recurring expenses, not one-time payments
- A large one-time expense followed by lower cash is normal business, not a pattern of crisis

Be thorough but practical. Focus on actionable insights.
EOT;

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            return $this->createInsight(
                user: $user,
                response: $response,
                triggerType: TriggerType::Scheduled,
                triggerContext: ['type' => 'weekly_analysis', 'week' => now()->format('Y-W')],
                insightType: InsightType::Analysis,
                title: 'Weekly Strategic Review',
                priority: InsightPriority::Medium
            );
        } catch (\Exception $e) {
            Log::error('Failed to generate weekly analysis', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function analyzeEvent(BusinessEvent $event): ?ProactiveInsight
    {
        if ($event->significance === EventSignificance::Low) {
            return null;
        }

        $user = $event->user;
        $context = $this->contextBuilder->build();

        $similarEvents = $this->findSimilarPastEvents($event);
        $prompt = $this->buildEventPromptWithHistory($event, $similarEvents);

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            $insightType = $this->determineInsightTypeForEvent($event);
            $priority = $this->mapSignificanceToPriority($event->significance);

            return $this->createInsight(
                user: $user,
                response: $response,
                triggerType: TriggerType::Event,
                triggerContext: [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type->value,
                ],
                insightType: $insightType,
                title: $this->generateEventInsightTitle($event),
                priority: $priority,
                relatedEvent: $event
            );
        } catch (\Exception $e) {
            Log::error('Failed to analyze event', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function analyzeThresholdBreach(User $user, string $type, array $context): ?ProactiveInsight
    {
        $advisoryContext = $this->contextBuilder->build();

        $prompt = match ($type) {
            'runway' => $this->buildRunwayThresholdPrompt($context),
            'contract_expiring' => $this->buildContractExpiringPrompt($context),
            default => null,
        };

        if (! $prompt) {
            return null;
        }

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $advisoryContext
            );

            return $this->createInsight(
                user: $user,
                response: $response,
                triggerType: TriggerType::Threshold,
                triggerContext: array_merge(['type' => $type], $context),
                insightType: InsightType::Warning,
                title: $this->generateThresholdTitle($type, $context),
                priority: InsightPriority::High
            );
        } catch (\Exception $e) {
            Log::error('Failed to analyze threshold breach', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function identifyOpportunities(User $user): ?ProactiveInsight
    {
        $context = $this->contextBuilder->build();
        $eventHistory = $this->formatRecentEvents($user, 30);

        $prompt = <<<EOT
Based on the current business position and recent events, identify any strategic opportunities that may not be immediately obvious.

{$eventHistory}

Consider:
1. Underutilized capacity or resources
2. Potential for upselling or expanding with existing clients
3. Market trends that could be leveraged
4. Cost optimization opportunities
5. Timing-based opportunities (contracts ending, renewals, etc.)

Only highlight genuine opportunities with actionable next steps. If there are no clear opportunities, say so briefly.
EOT;

        try {
            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            if ($this->containsNoOpportunities($response->content)) {
                return null;
            }

            return $this->createInsight(
                user: $user,
                response: $response,
                triggerType: TriggerType::Scheduled,
                triggerContext: ['type' => 'opportunity_scan', 'date' => now()->toDateString()],
                insightType: InsightType::Opportunity,
                title: 'Strategic Opportunity Identified',
                priority: InsightPriority::Medium
            );
        } catch (\Exception $e) {
            Log::error('Failed to identify opportunities', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function createInsight(
        User $user,
        LLMResponse $response,
        TriggerType $triggerType,
        array $triggerContext,
        InsightType $insightType,
        string $title,
        InsightPriority $priority,
        ?BusinessEvent $relatedEvent = null
    ): ProactiveInsight {
        return ProactiveInsight::create([
            'user_id' => $user->id,
            'trigger_type' => $triggerType,
            'trigger_context' => $triggerContext,
            'insight_type' => $insightType,
            'title' => $title,
            'content' => $response->content,
            'priority' => $priority,
            'is_read' => false,
            'is_dismissed' => false,
            'related_event_id' => $relatedEvent?->id,
            'provider' => $response->provider,
            'model' => $response->model,
            'tokens_used' => $response->tokensUsed,
        ]);
    }

    protected function formatRecentEvents(User $user, int $days): string
    {
        $events = $user->businessEvents()
            ->where('occurred_at', '>=', now()->subDays($days))
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        if ($events->isEmpty()) {
            return "RECENT BUSINESS EVENTS:\n  No significant events in the past {$days} days.";
        }

        $lines = ["RECENT BUSINESS EVENTS (past {$days} days):"];
        foreach ($events as $event) {
            $date = $event->occurred_at->format('M j');
            $significance = strtoupper($event->significance->value);
            $lines[] = "  [{$date}] [{$significance}] {$event->title}";
            if ($event->description) {
                $lines[] = "    {$event->description}";
            }
        }

        return implode("\n", $lines);
    }

    protected function buildEventPrompt(BusinessEvent $event): string
    {
        $type = $event->event_type;

        return match ($type) {
            EventType::ContractSigned => "A new contract was just signed: {$event->title}. {$event->description}\n\nAnalyze this development and provide strategic recommendations on how to maximize value from this new client relationship.",

            EventType::ContractExpired => "A contract has expired: {$event->title}. {$event->description}\n\nProvide recommendations on how to handle this situation - should we pursue renewal? What's the financial impact?",

            EventType::ContractEnding => "A contract is ending soon: {$event->title}. {$event->description}\n\nProvide a strategy for either renewing this contract or preparing for its end.",

            EventType::ExpenseChange => "There was a significant expense change: {$event->title}. {$event->description}\n\nAnalyze whether this change is concerning and recommend any actions.",

            EventType::RunwayThreshold => "A runway threshold was crossed: {$event->title}. {$event->description}\n\nProvide urgent recommendations for addressing this financial concern.",

            EventType::NewsAlert => "There's relevant market news: {$event->title}. {$event->description}\n\nAnalyze how this news might affect the business and recommend any actions.",

            EventType::ProductLaunched => "A new product was just launched: {$event->title}. {$event->description}\n\nProvide strategic recommendations for the launch phase: marketing priorities, early customer acquisition, pricing validation, and key metrics to track.",

            EventType::ProductRevenueChange => "Product revenue changed: {$event->title}. {$event->description}\n\nAnalyze this revenue change considering the product's effective hourly rate vs consulting. Should this product continue, be scaled, or sunset? Provide specific recommendations.",

            EventType::ProductMilestone => "A product development milestone was completed: {$event->title}. {$event->description}\n\nProvide recommendations for the next development phase and suggest priorities.",

            EventType::ProductStatusChange => "A product status changed: {$event->title}. {$event->description}\n\nAnalyze what this status change means for the product roadmap and resource allocation.",

            default => "The following business event occurred: {$event->title}. {$event->description}\n\nProvide relevant analysis and recommendations.",
        };
    }

    protected function determineInsightTypeForEvent(BusinessEvent $event): InsightType
    {
        return match ($event->event_type) {
            EventType::ContractSigned, EventType::ContractRenewed => InsightType::Opportunity,
            EventType::ContractExpired, EventType::ContractEnding, EventType::RunwayThreshold => InsightType::Warning,
            EventType::ExpenseChange => $this->isNegativeExpenseChange($event) ? InsightType::Warning : InsightType::Analysis,
            EventType::NewsAlert => InsightType::Analysis,
            EventType::ProductLaunched => InsightType::Opportunity,
            EventType::ProductRevenueChange => InsightType::Analysis,
            EventType::ProductMilestone => InsightType::Analysis,
            EventType::ProductStatusChange => InsightType::Analysis,
            default => InsightType::Recommendation,
        };
    }

    protected function isNegativeExpenseChange(BusinessEvent $event): bool
    {
        $metadata = $event->metadata ?? [];
        $changeType = $metadata['change_type'] ?? '';

        return $changeType === 'increased' || $changeType === 'created';
    }

    protected function mapSignificanceToPriority(EventSignificance $significance): InsightPriority
    {
        return match ($significance) {
            EventSignificance::Critical => InsightPriority::Urgent,
            EventSignificance::High => InsightPriority::High,
            EventSignificance::Medium => InsightPriority::Medium,
            EventSignificance::Low => InsightPriority::Low,
        };
    }

    protected function generateEventInsightTitle(BusinessEvent $event): string
    {
        return match ($event->event_type) {
            EventType::ContractSigned => 'New Contract Strategy',
            EventType::ContractExpired => 'Contract Expiration Analysis',
            EventType::ContractEnding => 'Contract Renewal Opportunity',
            EventType::ContractRenewed => 'Contract Renewed - Next Steps',
            EventType::ExpenseChange => 'Expense Analysis',
            EventType::RunwayThreshold => 'Runway Alert - Action Required',
            EventType::NewsAlert => 'Market Intelligence Update',
            EventType::ProductLaunched => 'Product Launch Strategy',
            EventType::ProductRevenueChange => 'Product Revenue Analysis',
            EventType::ProductMilestone => 'Development Progress Update',
            EventType::ProductStatusChange => 'Product Status Update',
            default => 'Business Event Analysis',
        };
    }

    protected function buildRunwayThresholdPrompt(array $context): string
    {
        $currentRunway = $context['current_runway'] ?? 'unknown';
        $threshold = $context['threshold'] ?? 'unknown';
        $crossedBelow = $context['crossed_below'] ?? true;

        if ($crossedBelow) {
            return <<<EOT
Business runway has dropped to {$currentRunway} months, below the {$threshold} month warning threshold.

BEFORE recommending drastic action, check:
1. Was there a recent ONE-TIME expense (tax payment, equipment purchase) that explains the cash decrease?
2. Is the RECURRING monthly income still exceeding RECURRING monthly expenses?
3. If recurring cashflow is positive, this may be a temporary dip, not a crisis

If this is due to a planned one-time expense and recurring cashflow is healthy:
- Acknowledge the temporary impact
- Note when cash should recover based on recurring income
- Suggest any sensible timing adjustments for future large payments

Only if there's a genuine recurring cashflow problem, provide recommendations to:
1. Reduce recurring expenses
2. Accelerate revenue
3. Consider funding options if truly needed
EOT;
        }

        return "Business runway has recovered to {$currentRunway} months, above the {$threshold} month threshold.\n\nProvide recommendations for:\n1. Maintaining this improved position\n2. Building additional buffer\n3. Preventing future threshold breaches";
    }

    protected function buildContractExpiringPrompt(array $context): string
    {
        $contractName = $context['contract_name'] ?? 'a contract';
        $daysRemaining = $context['days_remaining'] ?? 'few';
        $monthlyValue = $context['monthly_value'] ?? 0;

        return "Contract '{$contractName}' worth \${$monthlyValue}/month is expiring in {$daysRemaining} days.\n\nProvide a renewal strategy including:\n1. Timing and approach for renewal discussions\n2. Value propositions to emphasize\n3. Contingency plan if renewal fails\n4. Impact analysis if lost";
    }

    protected function generateThresholdTitle(string $type, array $context): string
    {
        return match ($type) {
            'runway' => $context['crossed_below'] ?? true
                ? 'Runway Alert - Immediate Action Required'
                : 'Runway Recovery - Stabilization Strategy',
            'contract_expiring' => "Contract Renewal Strategy - {$context['contract_name']}",
            default => 'Threshold Alert Analysis',
        };
    }

    protected function determinePriorityFromContent(string $content): InsightPriority
    {
        $urgentKeywords = ['urgent', 'immediately', 'critical', 'emergency', 'action required', 'warning'];
        $lowContent = strtolower($content);

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($lowContent, $keyword)) {
                return InsightPriority::High;
            }
        }

        return InsightPriority::Medium;
    }

    protected function containsNoOpportunities(string $content): bool
    {
        $negativeIndicators = [
            'no clear opportunities',
            'no obvious opportunities',
            'no significant opportunities',
            'no immediate opportunities',
            'cannot identify any',
            'don\'t see any',
            'there are no',
        ];

        $lowContent = strtolower($content);

        foreach ($negativeIndicators as $indicator) {
            if (str_contains($lowContent, $indicator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find similar past events using vector search.
     *
     * @return Collection<int, object>
     */
    protected function findSimilarPastEvents(BusinessEvent $event): Collection
    {
        if (! $this->vectorSearch) {
            return collect();
        }

        $eventText = $event->title."\n".($event->description ?? '');

        return $this->vectorSearch->findSimilarEvents(
            userId: $event->user_id,
            eventDescription: $eventText,
            limit: 3,
            threshold: 0.4,
            excludeEventId: $event->id
        );
    }

    /**
     * Build event analysis prompt with historical context.
     *
     * @param  Collection<int, object>  $similarEvents
     */
    protected function buildEventPromptWithHistory(BusinessEvent $event, Collection $similarEvents): string
    {
        $basePrompt = $this->buildEventPrompt($event);

        if ($similarEvents->isEmpty()) {
            return $basePrompt;
        }

        $historySection = "\n\nSIMILAR PAST EVENTS FOR CONTEXT:\n";

        foreach ($similarEvents as $pastEvent) {
            $date = \Carbon\Carbon::parse($pastEvent->occurred_at)->format('M j, Y');
            $historySection .= "  [{$date}] {$pastEvent->title}\n";
            if ($pastEvent->description) {
                $historySection .= '    '.Str::limit($pastEvent->description, 150)."\n";
            }
        }

        $historySection .= "\nConsider what happened after these similar events when providing your analysis and recommendations.";

        return $basePrompt.$historySection;
    }
}
