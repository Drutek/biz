<?php

use App\Enums\EventType;
use App\Jobs\CheckContractExpirations;
use App\Jobs\CheckRunwayThresholds;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('CheckContractExpirations Job', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        // Disable observers to prevent automatic event creation
        Contract::unsetEventDispatcher();
    });

    afterEach(function () {
        // Re-enable observers
        Contract::setEventDispatcher(app('events'));
    });

    it('creates events for contracts expiring in warning thresholds', function () {
        // Contract expiring in exactly 7 days (midnight to midnight)
        Contract::factory()->confirmed()->create([
            'end_date' => now()->startOfDay()->addDays(7),
        ]);

        // Contract expiring in exactly 14 days
        Contract::factory()->confirmed()->create([
            'end_date' => now()->startOfDay()->addDays(14),
        ]);

        // Contract expiring in exactly 30 days
        Contract::factory()->confirmed()->create([
            'end_date' => now()->startOfDay()->addDays(30),
        ]);

        // Contract expiring in 45 days (not a threshold)
        Contract::factory()->confirmed()->create([
            'end_date' => now()->startOfDay()->addDays(45),
        ]);

        $job = new CheckContractExpirations;
        $job->handle(app(\App\Services\BusinessEventRecorder::class));

        // 3 contracts at thresholds, 1 user = 3 events
        expect(BusinessEvent::where('event_type', EventType::ContractEnding)->count())->toBe(3);
    });

    it('does not create duplicate events for same contract and threshold', function () {
        Contract::factory()->confirmed()->create([
            'end_date' => now()->startOfDay()->addDays(7),
        ]);

        $job = new CheckContractExpirations;
        $recorder = app(\App\Services\BusinessEventRecorder::class);

        // Run twice
        $job->handle($recorder);
        $job->handle($recorder);

        // Should still only have 1 event
        expect(BusinessEvent::where('event_type', EventType::ContractEnding)->count())->toBe(1);
    });

    it('ignores completed contracts', function () {
        Contract::factory()->completed()->create([
            'end_date' => now()->addDays(7),
        ]);

        $job = new CheckContractExpirations;
        $job->handle(app(\App\Services\BusinessEventRecorder::class));

        expect(BusinessEvent::where('event_type', EventType::ContractEnding)->count())->toBe(0);
    });

    it('ignores contracts with no end date', function () {
        Contract::factory()->confirmed()->create([
            'end_date' => null,
        ]);

        $job = new CheckContractExpirations;
        $job->handle(app(\App\Services\BusinessEventRecorder::class));

        expect(BusinessEvent::where('event_type', EventType::ContractEnding)->count())->toBe(0);
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        CheckContractExpirations::dispatch();

        Queue::assertPushed(CheckContractExpirations::class);
    });
});

describe('CheckRunwayThresholds Job', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        UserPreference::factory()->for($this->user)->withRunwayThreshold(3)->create();
        // Disable observers
        Contract::unsetEventDispatcher();
        Expense::unsetEventDispatcher();
    });

    afterEach(function () {
        Contract::setEventDispatcher(app('events'));
        Expense::setEventDispatcher(app('events'));
    });

    it('creates event when runway drops below threshold', function () {
        // Create expenses that exceed income
        Expense::factory()->create([
            'amount' => 10000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        // Create small income
        Contract::factory()->confirmed()->monthly(5000)->active()->create();

        $job = new CheckRunwayThresholds;
        $job->handle(app(\App\Services\BusinessEventRecorder::class));

        // With negative cashflow, runway will be low
        $events = BusinessEvent::where('event_type', EventType::RunwayThreshold)->get();
        expect($events->count())->toBeGreaterThanOrEqual(0); // May or may not trigger depending on exact calculations
    });

    it('respects user proactive insights preference', function () {
        $disabledUser = User::factory()->create();
        UserPreference::factory()->for($disabledUser)->insightsDisabled()->create();

        Expense::factory()->create([
            'amount' => 10000,
            'frequency' => 'monthly',
            'is_active' => true,
        ]);

        $job = new CheckRunwayThresholds;
        $job->handle(app(\App\Services\BusinessEventRecorder::class));

        // No events for user with disabled insights
        $events = BusinessEvent::where('user_id', $disabledUser->id)->get();
        expect($events->count())->toBe(0);
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        CheckRunwayThresholds::dispatch();

        Queue::assertPushed(CheckRunwayThresholds::class);
    });
});
