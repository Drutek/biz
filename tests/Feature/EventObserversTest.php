<?php

use App\Enums\ContractStatus;
use App\Enums\EventType;
use App\Models\BusinessEvent;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ContractObserver', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('records event when confirmed contract is created', function () {
        $contract = Contract::factory()->confirmed()->create();

        expect(BusinessEvent::where('event_type', EventType::ContractSigned)->count())->toBe(1);

        $event = BusinessEvent::where('event_type', EventType::ContractSigned)->first();
        expect($event->eventable_id)->toBe($contract->id)
            ->and($event->user_id)->toBe($this->user->id);
    });

    it('does not record event when pipeline contract is created', function () {
        Contract::factory()->pipeline()->create();

        expect(BusinessEvent::where('event_type', EventType::ContractSigned)->count())->toBe(0);
    });

    it('records event when pipeline contract becomes confirmed', function () {
        $contract = Contract::factory()->pipeline()->create();

        // No event yet
        expect(BusinessEvent::where('event_type', EventType::ContractSigned)->count())->toBe(0);

        // Update to confirmed
        $contract->update(['status' => ContractStatus::Confirmed]);

        expect(BusinessEvent::where('event_type', EventType::ContractSigned)->count())->toBe(1);
    });

    it('records event when contract becomes completed', function () {
        $contract = Contract::factory()->confirmed()->create();

        // Clear the creation event
        BusinessEvent::truncate();

        $contract->update(['status' => ContractStatus::Completed]);

        expect(BusinessEvent::where('event_type', EventType::ContractExpired)->count())->toBe(1);
    });

    it('records renewal when end date is extended', function () {
        $contract = Contract::factory()->confirmed()->create([
            'end_date' => now()->addMonth(),
        ]);

        // Clear the creation event
        BusinessEvent::truncate();

        $contract->update(['end_date' => now()->addYear()]);

        expect(BusinessEvent::where('event_type', EventType::ContractRenewed)->count())->toBe(1);
    });

    it('does not record event when not authenticated', function () {
        auth()->logout();

        Contract::factory()->confirmed()->create();

        expect(BusinessEvent::count())->toBe(0);
    });
});

describe('ExpenseObserver', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('records event when expense is created', function () {
        $expense = Expense::factory()->create();

        expect(BusinessEvent::where('event_type', EventType::ExpenseChange)->count())->toBe(1);

        $event = BusinessEvent::first();
        expect($event->metadata['change_type'])->toBe('created')
            ->and($event->user_id)->toBe($this->user->id);
    });

    it('records event when expense amount increases significantly', function () {
        $expense = Expense::factory()->create(['amount' => 1000]);

        // Clear the creation event
        BusinessEvent::truncate();

        // 20% increase - should trigger
        $expense->update(['amount' => 1200]);

        expect(BusinessEvent::count())->toBe(1);
        expect(BusinessEvent::first()->metadata['change_type'])->toBe('increased');
    });

    it('does not record event for insignificant changes', function () {
        $expense = Expense::factory()->create(['amount' => 1000]);

        // Clear the creation event
        BusinessEvent::truncate();

        // 5% increase - should NOT trigger
        $expense->update(['amount' => 1050]);

        expect(BusinessEvent::count())->toBe(0);
    });

    it('records event when expense is deleted', function () {
        $expense = Expense::factory()->create();

        // Clear the creation event
        BusinessEvent::truncate();

        $expense->delete();

        expect(BusinessEvent::where('event_type', EventType::ExpenseChange)->count())->toBe(1);
        expect(BusinessEvent::first()->metadata['change_type'])->toBe('deleted');
    });

    it('does not record event when not authenticated', function () {
        auth()->logout();

        Expense::factory()->create();

        expect(BusinessEvent::count())->toBe(0);
    });
});
