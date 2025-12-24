<?php

use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Enums\TriggerType;
use App\Events\BusinessEventRecorded;
use App\Jobs\GenerateDailyInsights;
use App\Jobs\GenerateWeeklyInsights;
use App\Listeners\GenerateInsightOnEvent;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\ProactiveInsight;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\AdvisoryContextBuilder;
use App\Services\LLM\LLMManager;
use App\Services\LLM\LLMResponse;
use App\Services\ProactiveAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    UserPreference::factory()->for($this->user)->create();
});

describe('ProactiveAdvisor Service', function () {
    it('generates daily analysis insight', function () {
        $mockResponse = new LLMResponse(
            content: 'Your business is performing well. No urgent actions needed.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 150
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->generateDailyAnalysis($this->user);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->trigger_type)->toBe(TriggerType::Scheduled)
            ->and($insight->insight_type)->toBe(InsightType::Analysis)
            ->and($insight->title)->toBe('Daily Business Analysis')
            ->and($insight->user_id)->toBe($this->user->id);
    });

    it('generates weekly analysis insight', function () {
        $mockResponse = new LLMResponse(
            content: 'Weekly strategic review: Consider expanding marketing efforts.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 300
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->generateWeeklyAnalysis($this->user);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->title)->toBe('Weekly Strategic Review')
            ->and($insight->priority)->toBe(InsightPriority::Medium);
    });

    it('analyzes high significance events', function () {
        $event = BusinessEvent::factory()->for($this->user)->create([
            'event_type' => EventType::ContractSigned,
            'significance' => EventSignificance::High,
            'title' => 'Contract signed: Big Client',
            'description' => 'New contract worth $10,000',
        ]);

        $mockResponse = new LLMResponse(
            content: 'Great opportunity! Maximize value from this new client.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 200
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->analyzeEvent($event);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->trigger_type)->toBe(TriggerType::Event)
            ->and($insight->related_event_id)->toBe($event->id)
            ->and($insight->trigger_context['event_id'])->toBe($event->id);
    });

    it('skips low significance events', function () {
        $event = BusinessEvent::factory()->for($this->user)->create([
            'significance' => EventSignificance::Low,
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->analyzeEvent($event);

        expect($insight)->toBeNull();
    });

    it('analyzes runway threshold breach', function () {
        $mockResponse = new LLMResponse(
            content: 'URGENT: Runway is critically low. Immediate action required.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 250
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->analyzeThresholdBreach($this->user, 'runway', [
            'current_runway' => 2.5,
            'threshold' => 3,
            'crossed_below' => true,
        ]);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->trigger_type)->toBe(TriggerType::Threshold)
            ->and($insight->insight_type)->toBe(InsightType::Warning)
            ->and($insight->priority)->toBe(InsightPriority::High);
    });

    it('identifies opportunities when present', function () {
        $mockResponse = new LLMResponse(
            content: 'Opportunity: Client X contract is ending. Consider upselling.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 180
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->identifyOpportunities($this->user);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->insight_type)->toBe(InsightType::Opportunity);
    });

    it('returns null when no opportunities found', function () {
        $mockResponse = new LLMResponse(
            content: 'There are no clear opportunities at this time.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->identifyOpportunities($this->user);

        expect($insight)->toBeNull();
    });

    it('handles LLM errors gracefully', function () {
        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andThrow(new \Exception('API Error'));

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->generateDailyAnalysis($this->user);

        expect($insight)->toBeNull();
    });

    it('determines priority from content with urgent keywords', function () {
        $mockResponse = new LLMResponse(
            content: 'URGENT: Action required immediately! Critical issue detected.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 100
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);

        $insight = $advisor->generateDailyAnalysis($this->user);

        expect($insight->priority)->toBe(InsightPriority::High);
    });
});

describe('BusinessEventRecorded Event', function () {
    it('is dispatched when business event is recorded', function () {
        Event::fake([BusinessEventRecorded::class]);

        $contract = Contract::factory()->confirmed()->create();

        $recorder = app(\App\Services\BusinessEventRecorder::class);
        $recorder->recordContractSigned($contract, $this->user);

        Event::assertDispatched(BusinessEventRecorded::class);
    });

    it('is not dispatched for AI insight events', function () {
        Event::fake([BusinessEventRecorded::class]);

        $insight = ProactiveInsight::factory()->for($this->user)->create();

        $recorder = app(\App\Services\BusinessEventRecorder::class);
        $recorder->recordAiInsight($insight, $this->user);

        Event::assertNotDispatched(BusinessEventRecorded::class);
    });
});

describe('GenerateInsightOnEvent Listener', function () {
    it('generates insight for high significance events', function () {
        $event = BusinessEvent::factory()->for($this->user)->create([
            'significance' => EventSignificance::High,
            'event_type' => EventType::ContractSigned,
        ]);

        $mockResponse = new LLMResponse(
            content: 'Analysis of the new contract.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 100
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);
        $listener = new GenerateInsightOnEvent($advisor);

        $listener->handle(new BusinessEventRecorded($event));

        expect(ProactiveInsight::count())->toBe(1);
    });

    it('skips low significance events', function () {
        $event = BusinessEvent::factory()->for($this->user)->create([
            'significance' => EventSignificance::Low,
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);
        $listener = new GenerateInsightOnEvent($advisor);

        $listener->handle(new BusinessEventRecorded($event));

        expect(ProactiveInsight::count())->toBe(0);
    });

    it('respects user preference for proactive insights', function () {
        $this->user->preferences->update(['proactive_insights_enabled' => false]);

        $event = BusinessEvent::factory()->for($this->user)->create([
            'significance' => EventSignificance::High,
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $advisor = new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder);
        $listener = new GenerateInsightOnEvent($advisor);

        $listener->handle(new BusinessEventRecorded($event));

        expect(ProactiveInsight::count())->toBe(0);
    });
});

describe('GenerateDailyInsights Job', function () {
    it('generates insights for users with enabled preferences', function () {
        $mockResponse = new LLMResponse(
            content: 'Daily analysis content',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 100
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        app()->instance(LLMManager::class, $mockManager);

        $job = new GenerateDailyInsights;
        $job->handle(
            new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder),
            app(\App\Services\BusinessEventRecorder::class)
        );

        expect(ProactiveInsight::count())->toBe(1);
    });

    it('skips users with disabled preferences', function () {
        $this->user->preferences->update(['proactive_insights_enabled' => false]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $job = new GenerateDailyInsights;
        $job->handle(
            new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder),
            app(\App\Services\BusinessEventRecorder::class)
        );

        expect(ProactiveInsight::count())->toBe(0);
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        GenerateDailyInsights::dispatch();

        Queue::assertPushed(GenerateDailyInsights::class);
    });
});

describe('GenerateWeeklyInsights Job', function () {
    it('generates weekly and opportunity insights', function () {
        $mockResponse = new LLMResponse(
            content: 'Weekly strategic review and opportunity scan.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 200
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        app()->instance(LLMManager::class, $mockManager);

        $job = new GenerateWeeklyInsights;
        $job->handle(
            new ProactiveAdvisor($mockManager, new AdvisoryContextBuilder),
            app(\App\Services\BusinessEventRecorder::class)
        );

        // Weekly + opportunity insights
        expect(ProactiveInsight::count())->toBe(2);
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        GenerateWeeklyInsights::dispatch();

        Queue::assertPushed(GenerateWeeklyInsights::class);
    });
});

describe('AdvisoryContextBuilder with Events', function () {
    it('includes event history in context when user provided', function () {
        BusinessEvent::factory()->for($this->user)->create([
            'title' => 'Test Event for Context',
            'occurred_at' => now()->subDay(),
        ]);

        $builder = new AdvisoryContextBuilder;
        $context = $builder->build($this->user);

        expect($context)->toContain('RECENT BUSINESS EVENTS')
            ->and($context)->toContain('Test Event for Context');
    });

    it('excludes event history when no user provided', function () {
        BusinessEvent::factory()->for($this->user)->create();

        $builder = new AdvisoryContextBuilder;
        $context = $builder->build();

        expect($context)->not->toContain('RECENT BUSINESS EVENTS');
    });

    it('excludes old events from history', function () {
        BusinessEvent::factory()->for($this->user)->create([
            'title' => 'Old Event',
            'occurred_at' => now()->subDays(60),
        ]);

        $builder = new AdvisoryContextBuilder;
        $context = $builder->build($this->user);

        expect($context)->not->toContain('Old Event');
    });
});
