<?php

use App\Enums\EventSignificance;
use App\Jobs\DispatchDailyStandups;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\DailyStandup;
use App\Models\Expense;
use App\Models\ProactiveInsight;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\DailyStandupNotification;
use App\Services\AdvisoryContextBuilder;
use App\Services\LLM\LLMManager;
use App\Services\LLM\LLMResponse;
use App\Services\StandupGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    UserPreference::factory()->for($this->user)->create();
    Contract::unsetEventDispatcher();
    Expense::unsetEventDispatcher();
});

afterEach(function () {
    Contract::setEventDispatcher(app('events'));
    Expense::setEventDispatcher(app('events'));
});

describe('StandupGenerator Service', function () {
    it('generates a daily standup for a user', function () {
        $mockResponse = new LLMResponse(
            content: 'Your business is stable. Focus on client retention today.',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 100
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $standup = $generator->generate($this->user);

        expect($standup)->toBeInstanceOf(DailyStandup::class)
            ->and($standup->user_id)->toBe($this->user->id)
            ->and($standup->standup_date->toDateString())->toBe(now()->toDateString())
            ->and($standup->financial_snapshot)->toBeArray()
            ->and($standup->ai_summary)->not->toBeNull();
    });

    it('returns existing standup if already generated for today', function () {
        $existingStandup = DailyStandup::factory()->for($this->user)->create([
            'standup_date' => now()->toDateString(),
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $standup = $generator->generate($this->user);

        expect($standup->id)->toBe($existingStandup->id);
    });

    it('includes financial snapshot in standup', function () {
        Contract::factory()->confirmed()->monthly(5000)->create();
        Expense::factory()->create(['amount' => 2000, 'frequency' => 'monthly']);

        $mockResponse = new LLMResponse(
            content: 'Summary content',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $standup = $generator->generate($this->user);

        expect($standup->financial_snapshot)
            ->toHaveKey('monthly_income')
            ->toHaveKey('monthly_expenses')
            ->toHaveKey('monthly_net')
            ->toHaveKey('runway_months');
    });

    it('detects contract expiration alerts', function () {
        Contract::factory()->confirmed()->create([
            'end_date' => now()->addDays(5),
            'name' => 'Expiring Contract',
        ]);

        $mockResponse = new LLMResponse(
            content: 'Summary',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $alerts = $generator->getAlerts($this->user);

        expect($alerts)->toHaveKey('contracts_expiring')
            ->and($alerts['contracts_expiring'])->toHaveCount(1)
            ->and($alerts['contracts_expiring'][0]['name'])->toBe('Expiring Contract');
    });

    it('detects runway alerts when below threshold', function () {
        $this->user->preferences->update(['runway_alert_threshold' => 6]);
        $this->user->refresh(); // Refresh to get updated preferences

        Contract::factory()->confirmed()->monthly(1000)->create();
        Expense::factory()->create(['amount' => 5000, 'frequency' => 'monthly']);

        $mockResponse = new LLMResponse(
            content: 'Summary',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $alerts = $generator->getAlerts($this->user);

        expect($alerts)->toHaveKey('runway');
    });

    it('includes urgent events in alerts', function () {
        BusinessEvent::factory()->for($this->user)->create([
            'significance' => EventSignificance::Critical,
            'occurred_at' => now()->subHours(2),
        ]);

        $mockResponse = new LLMResponse(
            content: 'Summary',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $alerts = $generator->getAlerts($this->user);

        expect($alerts)->toHaveKey('urgent_events')
            ->and($alerts['urgent_events'])->toHaveCount(1);
    });

    it('includes unread insights count in alerts', function () {
        ProactiveInsight::factory()->for($this->user)->unread()->count(3)->create();

        $mockResponse = new LLMResponse(
            content: 'Summary',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $alerts = $generator->getAlerts($this->user);

        expect($alerts)->toHaveKey('unread_insights')
            ->and($alerts['unread_insights'])->toBe(3);
    });

    it('skips AI summary when proactive insights disabled', function () {
        $this->user->preferences->update(['proactive_insights_enabled' => false]);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldNotReceive('driver');

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);
        $standup = $generator->generate($this->user);

        expect($standup->ai_summary)->toBeNull();
    });

    it('retrieves recent events', function () {
        BusinessEvent::factory()->for($this->user)->count(5)->create([
            'occurred_at' => now()->subDays(3),
        ]);
        BusinessEvent::factory()->for($this->user)->create([
            'occurred_at' => now()->subDays(10),
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);

        $recentEvents = $generator->getRecentEvents($this->user, 7);

        expect($recentEvents)->toHaveCount(5);
    });
});

describe('DailyStandupNotification', function () {
    it('sends via mail and database channels', function () {
        $standup = DailyStandup::factory()->for($this->user)->create();

        $notification = new DailyStandupNotification($standup);

        expect($notification->via($this->user))->toBe(['mail', 'database']);
    });

    it('builds correct mail message', function () {
        $standup = DailyStandup::factory()->for($this->user)->create([
            'financial_snapshot' => [
                'company_name' => 'Test Company',
                'monthly_income' => 10000,
                'monthly_expenses' => 5000,
                'monthly_net' => 5000,
                'runway_months' => 12,
            ],
            'alerts' => [
                'contracts_expiring' => [
                    ['name' => 'Contract A', 'days_remaining' => 7],
                ],
            ],
            'ai_summary' => 'Everything looks good today.',
        ]);

        $notification = new DailyStandupNotification($standup);
        $mail = $notification->toMail($this->user);

        expect($mail->subject)->toContain('Test Company')
            ->and($mail->subject)->toContain('Daily Business Briefing');
    });

    it('builds correct array representation', function () {
        $standup = DailyStandup::factory()->for($this->user)->create([
            'financial_snapshot' => [
                'monthly_net' => 5000,
                'runway_months' => 12,
            ],
            'alerts' => [
                'contracts_expiring' => [
                    ['name' => 'Contract A', 'days_remaining' => 7],
                ],
                'runway' => ['current_runway' => 2.5, 'threshold' => 3],
            ],
        ]);

        $notification = new DailyStandupNotification($standup);
        $array = $notification->toArray($this->user);

        expect($array)
            ->toHaveKey('standup_id')
            ->toHaveKey('has_alerts')
            ->toHaveKey('alert_count')
            ->and($array['has_alerts'])->toBeTrue()
            ->and($array['alert_count'])->toBe(2);
    });
});

describe('DispatchDailyStandups Job', function () {
    it('generates and sends standup to eligible users', function () {
        Notification::fake();

        $this->user->preferences->update([
            'standup_email_enabled' => true,
            'standup_email_time' => now()->format('H:i'),
        ]);

        $mockResponse = new LLMResponse(
            content: 'Daily summary',
            provider: 'claude',
            model: 'claude-3-sonnet',
            tokensUsed: 50
        );

        $mockProvider = Mockery::mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);

        $mockManager = Mockery::mock(LLMManager::class);
        $mockManager->shouldReceive('driver')->andReturn($mockProvider);

        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);

        $job = new DispatchDailyStandups;
        $job->handle($generator);

        Notification::assertSentTo($this->user, DailyStandupNotification::class);
    });

    it('skips users with disabled email notifications', function () {
        Notification::fake();

        $this->user->preferences->update([
            'standup_email_enabled' => false,
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);

        $job = new DispatchDailyStandups;
        $job->handle($generator);

        Notification::assertNotSentTo($this->user, DailyStandupNotification::class);
    });

    it('does not send duplicate emails for same day', function () {
        Notification::fake();

        $this->user->preferences->update([
            'standup_email_enabled' => true,
            'standup_email_time' => now()->format('H:i'),
        ]);

        DailyStandup::factory()->for($this->user)->create([
            'standup_date' => now()->toDateString(),
            'email_sent_at' => now(),
        ]);

        $mockManager = Mockery::mock(LLMManager::class);
        $generator = new StandupGenerator(new AdvisoryContextBuilder, $mockManager);

        $job = new DispatchDailyStandups;
        $job->handle($generator);

        Notification::assertNotSentTo($this->user, DailyStandupNotification::class);
    });

    it('marks standup as email sent after sending', function () {
        $standup = DailyStandup::factory()->for($this->user)->create([
            'standup_date' => now()->toDateString(),
            'email_sent_at' => null,
        ]);

        expect($standup->email_sent_at)->toBeNull();

        $standup->markEmailSent();

        expect($standup->fresh()->email_sent_at)->not->toBeNull();
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        DispatchDailyStandups::dispatch();

        Queue::assertPushed(DispatchDailyStandups::class);
    });
});
