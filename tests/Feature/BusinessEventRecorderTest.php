<?php

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\NewsItem;
use App\Models\ProactiveInsight;
use App\Models\TrackedEntity;
use App\Models\User;
use App\Services\BusinessEventRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->recorder = new BusinessEventRecorder;
});

describe('BusinessEventRecorder', function () {
    describe('Contract Events', function () {
        it('records contract signed event', function () {
            $contract = Contract::factory()->confirmed()->create([
                'name' => 'Big Client',
                'value' => 5000,
            ]);

            $event = $this->recorder->recordContractSigned($contract, $this->user);

            expect($event)->toBeInstanceOf(BusinessEvent::class)
                ->and($event->event_type)->toBe(EventType::ContractSigned)
                ->and($event->category)->toBe(EventCategory::Financial)
                ->and($event->title)->toContain('Big Client')
                ->and($event->eventable_id)->toBe($contract->id)
                ->and($event->metadata['contract_id'])->toBe($contract->id);
        });

        it('records contract renewed event', function () {
            $contract = Contract::factory()->confirmed()->create();

            $event = $this->recorder->recordContractRenewed($contract, $this->user);

            expect($event->event_type)->toBe(EventType::ContractRenewed)
                ->and($event->significance)->toBe(EventSignificance::High);
        });

        it('records contract expired event', function () {
            $contract = Contract::factory()->completed()->create();

            $event = $this->recorder->recordContractExpired($contract, $this->user);

            expect($event->event_type)->toBe(EventType::ContractExpired)
                ->and($event->significance)->toBe(EventSignificance::High);
        });

        it('records contract ending event with correct significance', function () {
            $contract = Contract::factory()->confirmed()->create();

            // 7 days = critical
            $event7 = $this->recorder->recordContractEnding($contract, $this->user, 7);
            expect($event7->significance)->toBe(EventSignificance::Critical);

            // 14 days = high
            $event14 = $this->recorder->recordContractEnding($contract, $this->user, 14);
            expect($event14->significance)->toBe(EventSignificance::High);

            // 30 days = medium
            $event30 = $this->recorder->recordContractEnding($contract, $this->user, 30);
            expect($event30->significance)->toBe(EventSignificance::Medium);
        });

        it('determines contract significance based on value', function () {
            $highValue = Contract::factory()->confirmed()->monthly(15000)->create();
            $medValue = Contract::factory()->confirmed()->monthly(3000)->create();
            $lowValue = Contract::factory()->confirmed()->monthly(500)->create();

            $highEvent = $this->recorder->recordContractSigned($highValue, $this->user);
            $medEvent = $this->recorder->recordContractSigned($medValue, $this->user);
            $lowEvent = $this->recorder->recordContractSigned($lowValue, $this->user);

            expect($highEvent->significance)->toBe(EventSignificance::Critical)
                ->and($medEvent->significance)->toBe(EventSignificance::Medium)
                ->and($lowEvent->significance)->toBe(EventSignificance::Low);
        });
    });

    describe('Expense Events', function () {
        it('records expense created event', function () {
            $expense = Expense::factory()->create([
                'name' => 'Office Rent',
                'amount' => 2000,
            ]);

            $event = $this->recorder->recordExpenseChange($expense, $this->user, 'created');

            expect($event->event_type)->toBe(EventType::ExpenseChange)
                ->and($event->category)->toBe(EventCategory::Financial)
                ->and($event->title)->toContain('Office Rent')
                ->and($event->metadata['change_type'])->toBe('created');
        });

        it('records expense increased event with percentage', function () {
            $expense = Expense::factory()->create(['amount' => 1500]);

            $event = $this->recorder->recordExpenseChange($expense, $this->user, 'increased', 1000);

            expect($event->title)->toContain('increased')
                ->and($event->metadata['old_amount'])->toEqual(1000)
                ->and($event->metadata['new_amount'])->toEqual(1500)
                ->and($event->metadata['change_percent'])->toEqual(50.0);
        });

        it('records expense decreased event', function () {
            $expense = Expense::factory()->create(['amount' => 500]);

            $event = $this->recorder->recordExpenseChange($expense, $this->user, 'decreased', 1000);

            expect($event->title)->toContain('decreased')
                ->and($event->metadata['change_percent'])->toEqual(-50.0);
        });

        it('records expense deleted event', function () {
            $expense = Expense::factory()->create();

            $event = $this->recorder->recordExpenseChange($expense, $this->user, 'deleted');

            expect($event->metadata['change_type'])->toBe('deleted')
                ->and($event->eventable_id)->toBeNull();
        });
    });

    describe('Runway Events', function () {
        it('records runway threshold crossed below event', function () {
            $event = $this->recorder->recordRunwayThreshold($this->user, 2.5, 3, true);

            expect($event->event_type)->toBe(EventType::RunwayThreshold)
                ->and($event->significance)->toBe(EventSignificance::Critical)
                ->and($event->metadata['crossed_below'])->toBeTrue()
                ->and($event->metadata['current_runway'])->toBe(2.5)
                ->and($event->metadata['threshold'])->toBe(3);
        });

        it('records runway recovery event', function () {
            $event = $this->recorder->recordRunwayThreshold($this->user, 5.0, 3, false);

            expect($event->significance)->toBe(EventSignificance::High)
                ->and($event->metadata['crossed_below'])->toBeFalse()
                ->and($event->title)->toContain('recovered');
        });
    });

    describe('News Events', function () {
        it('records news alert event', function () {
            $entity = TrackedEntity::factory()->create(['name' => 'Competitor Inc']);
            $newsItem = NewsItem::factory()->forEntity($entity)->create([
                'title' => 'Breaking News',
                'source' => 'TechCrunch',
            ]);

            $event = $this->recorder->recordNewsAlert($newsItem, $this->user);

            expect($event->event_type)->toBe(EventType::NewsAlert)
                ->and($event->category)->toBe(EventCategory::Market)
                ->and($event->metadata['entity_name'])->toBe('Competitor Inc')
                ->and($event->metadata['source'])->toBe('TechCrunch');
        });
    });

    describe('AI Insight Events', function () {
        it('records AI insight event', function () {
            $insight = ProactiveInsight::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Revenue Opportunity',
                'insight_type' => InsightType::Opportunity,
                'priority' => InsightPriority::High,
            ]);

            $event = $this->recorder->recordAiInsight($insight, $this->user);

            expect($event->event_type)->toBe(EventType::AiInsight)
                ->and($event->category)->toBe(EventCategory::Advisory)
                ->and($event->significance)->toBe(EventSignificance::High)
                ->and($event->metadata['insight_type'])->toBe('opportunity');
        });

        it('maps insight priority to significance correctly', function () {
            $urgentInsight = ProactiveInsight::factory()->urgent()->create(['user_id' => $this->user->id]);
            $lowInsight = ProactiveInsight::factory()->low()->create(['user_id' => $this->user->id]);

            $urgentEvent = $this->recorder->recordAiInsight($urgentInsight, $this->user);
            $lowEvent = $this->recorder->recordAiInsight($lowInsight, $this->user);

            expect($urgentEvent->significance)->toBe(EventSignificance::Critical)
                ->and($lowEvent->significance)->toBe(EventSignificance::Low);
        });
    });

    describe('Manual Events', function () {
        it('records manual event', function () {
            $event = $this->recorder->recordManualEvent(
                $this->user,
                'Reached 100 customers',
                'Milestone achieved!',
                EventCategory::Milestone,
                EventSignificance::High
            );

            expect($event->event_type)->toBe(EventType::Manual)
                ->and($event->category)->toBe(EventCategory::Milestone)
                ->and($event->title)->toBe('Reached 100 customers')
                ->and($event->significance)->toBe(EventSignificance::High);
        });
    });
});
