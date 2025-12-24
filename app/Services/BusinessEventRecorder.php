<?php

namespace App\Services;

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Events\BusinessEventRecorded;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\NewsItem;
use App\Models\ProactiveInsight;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BusinessEventRecorder
{
    public function recordContractSigned(Contract $contract, User $user): BusinessEvent
    {
        return $this->record(
            user: $user,
            type: EventType::ContractSigned,
            category: EventCategory::Financial,
            title: "Contract signed: {$contract->name}",
            description: "New contract worth {$this->formatCurrency($contract->value)} ({$contract->billing_frequency->label()})",
            significance: $this->determineContractSignificance($contract),
            eventable: $contract,
            metadata: [
                'contract_id' => $contract->id,
                'value' => $contract->value,
                'monthly_value' => $contract->monthlyValue(),
                'billing_frequency' => $contract->billing_frequency->value,
            ]
        );
    }

    public function recordContractRenewed(Contract $contract, User $user): BusinessEvent
    {
        return $this->record(
            user: $user,
            type: EventType::ContractRenewed,
            category: EventCategory::Financial,
            title: "Contract renewed: {$contract->name}",
            description: "Contract renewed worth {$this->formatCurrency($contract->value)} ({$contract->billing_frequency->label()})",
            significance: EventSignificance::High,
            eventable: $contract,
            metadata: [
                'contract_id' => $contract->id,
                'value' => $contract->value,
            ]
        );
    }

    public function recordContractExpired(Contract $contract, User $user): BusinessEvent
    {
        return $this->record(
            user: $user,
            type: EventType::ContractExpired,
            category: EventCategory::Financial,
            title: "Contract expired: {$contract->name}",
            description: "Contract worth {$this->formatCurrency($contract->monthlyValue())}/month has ended",
            significance: EventSignificance::High,
            eventable: $contract,
            metadata: [
                'contract_id' => $contract->id,
                'monthly_value_lost' => $contract->monthlyValue(),
            ]
        );
    }

    public function recordContractEnding(Contract $contract, User $user, int $daysUntilEnd): BusinessEvent
    {
        $significance = match (true) {
            $daysUntilEnd <= 7 => EventSignificance::Critical,
            $daysUntilEnd <= 14 => EventSignificance::High,
            default => EventSignificance::Medium,
        };

        return $this->record(
            user: $user,
            type: EventType::ContractEnding,
            category: EventCategory::Financial,
            title: "Contract ending soon: {$contract->name}",
            description: "Contract ends in {$daysUntilEnd} days - worth {$this->formatCurrency($contract->monthlyValue())}/month",
            significance: $significance,
            eventable: $contract,
            metadata: [
                'contract_id' => $contract->id,
                'days_until_end' => $daysUntilEnd,
                'end_date' => $contract->end_date?->toDateString(),
                'monthly_value' => $contract->monthlyValue(),
            ]
        );
    }

    public function recordExpenseChange(
        Expense $expense,
        User $user,
        string $changeType,
        ?float $oldAmount = null
    ): BusinessEvent {
        $title = match ($changeType) {
            'created' => "New expense added: {$expense->name}",
            'increased' => "Expense increased: {$expense->name}",
            'decreased' => "Expense decreased: {$expense->name}",
            'deleted' => "Expense removed: {$expense->name}",
            default => "Expense changed: {$expense->name}",
        };

        $changePercent = null;
        if ($oldAmount && $oldAmount > 0) {
            $changePercent = round((($expense->amount - $oldAmount) / $oldAmount) * 100, 1);
        }

        $description = match ($changeType) {
            'created' => "New {$expense->frequency->label()} expense of {$this->formatCurrency($expense->amount)}",
            'increased' => "Increased from {$this->formatCurrency($oldAmount)} to {$this->formatCurrency($expense->amount)} ({$changePercent}%)",
            'decreased' => "Decreased from {$this->formatCurrency($oldAmount)} to {$this->formatCurrency($expense->amount)} ({$changePercent}%)",
            'deleted' => "Removed expense of {$this->formatCurrency($expense->amount)}",
            default => "Changed to {$this->formatCurrency($expense->amount)}",
        };

        return $this->record(
            user: $user,
            type: EventType::ExpenseChange,
            category: EventCategory::Financial,
            title: $title,
            description: $description,
            significance: $this->determineExpenseChangeSignificance($expense, $changeType, $changePercent),
            eventable: $changeType === 'deleted' ? null : $expense,
            metadata: [
                'expense_id' => $expense->id,
                'change_type' => $changeType,
                'old_amount' => $oldAmount,
                'new_amount' => $expense->amount,
                'change_percent' => $changePercent,
                'monthly_impact' => $expense->monthlyAmount(),
            ]
        );
    }

    public function recordRunwayThreshold(
        User $user,
        float $currentRunway,
        int $threshold,
        bool $crossedBelow = true
    ): BusinessEvent {
        $title = $crossedBelow
            ? "Runway dropped below {$threshold} months"
            : "Runway recovered above {$threshold} months";

        return $this->record(
            user: $user,
            type: EventType::RunwayThreshold,
            category: EventCategory::Financial,
            title: $title,
            description: 'Current runway: '.number_format($currentRunway, 1).' months',
            significance: $crossedBelow ? EventSignificance::Critical : EventSignificance::High,
            metadata: [
                'current_runway' => $currentRunway,
                'threshold' => $threshold,
                'crossed_below' => $crossedBelow,
            ]
        );
    }

    public function recordNewsAlert(NewsItem $newsItem, User $user): BusinessEvent
    {
        return $this->record(
            user: $user,
            type: EventType::NewsAlert,
            category: EventCategory::Market,
            title: "News: {$newsItem->trackedEntity->name}",
            description: $newsItem->title,
            significance: EventSignificance::Medium,
            eventable: $newsItem,
            metadata: [
                'news_item_id' => $newsItem->id,
                'tracked_entity_id' => $newsItem->tracked_entity_id,
                'entity_name' => $newsItem->trackedEntity->name,
                'source' => $newsItem->source,
                'url' => $newsItem->url,
            ]
        );
    }

    public function recordAiInsight(ProactiveInsight $insight, User $user): BusinessEvent
    {
        // Don't dispatch event for AI insights to avoid infinite loops
        return $this->record(
            user: $user,
            type: EventType::AiInsight,
            category: EventCategory::Advisory,
            title: "AI Insight: {$insight->title}",
            description: substr($insight->content, 0, 200).'...',
            significance: $this->mapInsightPriorityToSignificance($insight),
            eventable: $insight,
            metadata: [
                'insight_id' => $insight->id,
                'insight_type' => $insight->insight_type->value,
                'priority' => $insight->priority->value,
                'trigger_type' => $insight->trigger_type->value,
            ],
            dispatchEvent: false
        );
    }

    public function recordManualEvent(
        User $user,
        string $title,
        ?string $description,
        EventCategory $category,
        EventSignificance $significance = EventSignificance::Medium
    ): BusinessEvent {
        return $this->record(
            user: $user,
            type: EventType::Manual,
            category: $category,
            title: $title,
            description: $description,
            significance: $significance
        );
    }

    protected function record(
        User $user,
        EventType $type,
        EventCategory $category,
        string $title,
        ?string $description = null,
        EventSignificance $significance = EventSignificance::Medium,
        ?Model $eventable = null,
        ?array $metadata = null,
        bool $dispatchEvent = true
    ): BusinessEvent {
        $event = BusinessEvent::create([
            'user_id' => $user->id,
            'event_type' => $type,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'significance' => $significance,
            'eventable_type' => $eventable?->getMorphClass(),
            'eventable_id' => $eventable?->getKey(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        if ($dispatchEvent) {
            BusinessEventRecorded::dispatch($event);
        }

        return $event;
    }

    protected function formatCurrency(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    protected function determineContractSignificance(Contract $contract): EventSignificance
    {
        $monthlyValue = $contract->monthlyValue();

        return match (true) {
            $monthlyValue >= 10000 => EventSignificance::Critical,
            $monthlyValue >= 5000 => EventSignificance::High,
            $monthlyValue >= 1000 => EventSignificance::Medium,
            default => EventSignificance::Low,
        };
    }

    protected function determineExpenseChangeSignificance(
        Expense $expense,
        string $changeType,
        ?float $changePercent
    ): EventSignificance {
        if ($changeType === 'deleted') {
            return $expense->monthlyAmount() >= 1000
                ? EventSignificance::High
                : EventSignificance::Medium;
        }

        if ($changeType === 'created') {
            return $expense->monthlyAmount() >= 2000
                ? EventSignificance::High
                : EventSignificance::Medium;
        }

        if ($changePercent !== null && abs($changePercent) >= 50) {
            return EventSignificance::High;
        }

        if ($changePercent !== null && abs($changePercent) >= 20) {
            return EventSignificance::Medium;
        }

        return EventSignificance::Low;
    }

    protected function mapInsightPriorityToSignificance(ProactiveInsight $insight): EventSignificance
    {
        return match ($insight->priority) {
            \App\Enums\InsightPriority::Urgent => EventSignificance::Critical,
            \App\Enums\InsightPriority::High => EventSignificance::High,
            \App\Enums\InsightPriority::Medium => EventSignificance::Medium,
            \App\Enums\InsightPriority::Low => EventSignificance::Low,
        };
    }
}
