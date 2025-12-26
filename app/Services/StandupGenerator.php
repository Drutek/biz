<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\EventSignificance;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\DailyStandup;
use App\Models\Task;
use App\Models\User;
use App\Services\LLM\LLMManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StandupGenerator
{
    public function __construct(
        protected AdvisoryContextBuilder $contextBuilder,
        protected LLMManager $llmManager
    ) {}

    public function generate(User $user, ?Carbon $date = null): DailyStandup
    {
        $date = $date ?? now();

        $existingStandup = DailyStandup::query()
            ->where('user_id', $user->id)
            ->whereDate('standup_date', $date)
            ->first();

        if ($existingStandup) {
            return $existingStandup;
        }

        $snapshot = $this->contextBuilder->snapshot();
        $alerts = $this->getAlerts($user);
        $recentEvents = $this->getRecentEvents($user, 7);
        $aiSummary = $this->generateAiSummary($user, $snapshot, $alerts, $recentEvents);

        return DailyStandup::create([
            'user_id' => $user->id,
            'standup_date' => $date->toDateString(),
            'financial_snapshot' => $snapshot,
            'alerts' => $alerts,
            'ai_summary' => $aiSummary['summary'] ?? null,
            'ai_insights' => $aiSummary['insights'] ?? [],
            'generated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlerts(User $user): array
    {
        $alerts = [];

        $contractAlerts = $this->getContractExpirationAlerts();
        if (! empty($contractAlerts)) {
            $alerts['contracts_expiring'] = $contractAlerts;
        }

        $runwayAlert = $this->getRunwayAlert($user);
        if ($runwayAlert) {
            $alerts['runway'] = $runwayAlert;
        }

        $urgentEvents = $this->getUrgentEvents($user);
        if (! empty($urgentEvents)) {
            $alerts['urgent_events'] = $urgentEvents;
        }

        $unreadInsights = $this->getUnreadInsightsCount($user);
        if ($unreadInsights > 0) {
            $alerts['unread_insights'] = $unreadInsights;
        }

        $overdueTasks = $this->getOverdueTasks($user);
        if (! empty($overdueTasks)) {
            $alerts['overdue_tasks'] = $overdueTasks;
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getContractExpirationAlerts(): array
    {
        $alerts = [];
        $thresholds = [7, 14, 30];

        foreach ($thresholds as $days) {
            $contracts = Contract::query()
                ->whereIn('status', [ContractStatus::Confirmed, ContractStatus::Pipeline])
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', now())
                ->whereDate('end_date', '<=', now()->addDays($days))
                ->get();

            foreach ($contracts as $contract) {
                $daysRemaining = now()->startOfDay()->diffInDays($contract->end_date->startOfDay(), false);
                $alerts[] = [
                    'contract_id' => $contract->id,
                    'name' => $contract->name,
                    'days_remaining' => $daysRemaining,
                    'monthly_value' => $contract->monthlyValue(),
                    'end_date' => $contract->end_date->toDateString(),
                ];
            }
        }

        return collect($alerts)
            ->unique('contract_id')
            ->sortBy('days_remaining')
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getRunwayAlert(User $user): ?array
    {
        $preferences = $user->preferences ?? $user->getOrCreatePreferences();
        $calculator = new CashflowCalculator;
        $summary = $calculator->summary();

        $runwayMonths = $summary['runway_months'];

        if (is_infinite($runwayMonths)) {
            return null;
        }

        if ($runwayMonths <= $preferences->runway_alert_threshold) {
            return [
                'current_runway' => round($runwayMonths, 1),
                'threshold' => $preferences->runway_alert_threshold,
                'monthly_burn' => $summary['monthly_expenses'],
                'monthly_income' => $summary['monthly_income'],
            ];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getUrgentEvents(User $user): array
    {
        return BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDay())
            ->whereIn('significance', [EventSignificance::Critical, EventSignificance::High])
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'significance' => $event->significance->value,
                'category' => $event->category->value,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])
            ->toArray();
    }

    protected function getUnreadInsightsCount(User $user): int
    {
        return $user->unreadInsightsCount();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getOverdueTasks(User $user): array
    {
        return Task::query()
            ->where('user_id', $user->id)
            ->overdue()
            ->pending()
            ->orderByDesc('priority')
            ->limit(5)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority->value,
                'due_date' => $task->due_date->toDateString(),
                'days_overdue' => $task->daysOverdue(),
            ])
            ->toArray();
    }

    /**
     * @return \Illuminate\Support\Collection<int, BusinessEvent>
     */
    public function getRecentEvents(User $user, int $days = 7)
    {
        return BusinessEvent::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $alerts
     * @param  \Illuminate\Support\Collection<int, BusinessEvent>  $recentEvents
     * @return array<string, mixed>
     */
    protected function generateAiSummary(User $user, array $snapshot, array $alerts, $recentEvents): array
    {
        $preferences = $user->preferences ?? $user->getOrCreatePreferences();

        if (! $preferences->proactive_insights_enabled) {
            return ['summary' => null, 'insights' => []];
        }

        try {
            $prompt = $this->buildSummaryPrompt($snapshot, $alerts, $recentEvents);
            $context = $this->contextBuilder->build($user);

            $response = $this->llmManager->driver()->chat(
                messages: [['role' => 'user', 'content' => $prompt]],
                system: $context
            );

            return [
                'summary' => $response->content,
                'insights' => $this->extractInsights($response->content),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate standup AI summary', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['summary' => null, 'insights' => []];
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $alerts
     * @param  \Illuminate\Support\Collection<int, BusinessEvent>  $recentEvents
     */
    protected function buildSummaryPrompt(array $snapshot, array $alerts, $recentEvents): string
    {
        $alertsText = $this->formatAlertsForPrompt($alerts);
        $eventsText = $this->formatEventsForPrompt($recentEvents);

        return <<<EOT
Generate a concise daily business standup briefing.

CURRENT ALERTS:
{$alertsText}

RECENT EVENTS (PAST 7 DAYS):
{$eventsText}

Please provide:
1. A brief (2-3 sentence) summary of the current business status
2. Top 3 priorities for today (if any actions are needed)
3. Any risks or opportunities to be aware of

Be concise and actionable. Focus on what matters most today.
EOT;
    }

    /**
     * @param  array<string, mixed>  $alerts
     */
    protected function formatAlertsForPrompt(array $alerts): string
    {
        if (empty($alerts)) {
            return '  No active alerts.';
        }

        $lines = [];

        if (! empty($alerts['contracts_expiring'])) {
            foreach ($alerts['contracts_expiring'] as $contract) {
                $lines[] = "  - Contract '{$contract['name']}' expires in {$contract['days_remaining']} days (worth \${$contract['monthly_value']}/mo)";
            }
        }

        if (! empty($alerts['runway'])) {
            $lines[] = "  - RUNWAY ALERT: {$alerts['runway']['current_runway']} months remaining (threshold: {$alerts['runway']['threshold']} months)";
        }

        if (! empty($alerts['urgent_events'])) {
            $lines[] = '  - '.count($alerts['urgent_events']).' urgent events in the last 24 hours';
        }

        if (! empty($alerts['unread_insights'])) {
            $lines[] = "  - {$alerts['unread_insights']} unread AI insights";
        }

        return empty($lines) ? '  No active alerts.' : implode("\n", $lines);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BusinessEvent>  $events
     */
    protected function formatEventsForPrompt($events): string
    {
        if ($events->isEmpty()) {
            return '  No recent events.';
        }

        $lines = [];
        foreach ($events as $event) {
            $date = $event->occurred_at->format('M j');
            $lines[] = "  - [{$date}] {$event->title}";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    protected function extractInsights(string $content): array
    {
        $insights = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[\d\-\*\.]+\s*(.+)/', $line, $matches)) {
                $insight = trim($matches[1]);
                if (strlen($insight) > 10 && strlen($insight) < 500) {
                    $insights[] = $insight;
                }
            }
        }

        return array_slice($insights, 0, 5);
    }
}
