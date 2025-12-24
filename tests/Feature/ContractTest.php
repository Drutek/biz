<?php

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Contract Model', function () {
    it('can be created with valid data', function () {
        $contract = Contract::factory()->create([
            'name' => 'Acme Corp Consulting',
            'value' => 5000.00,
            'billing_frequency' => BillingFrequency::Monthly,
            'status' => ContractStatus::Confirmed,
        ]);

        expect($contract)->toBeInstanceOf(Contract::class)
            ->and($contract->name)->toBe('Acme Corp Consulting')
            ->and((float) $contract->value)->toBe(5000.00)
            ->and($contract->billing_frequency)->toBe(BillingFrequency::Monthly)
            ->and($contract->status)->toBe(ContractStatus::Confirmed);
    });

    it('casts dates correctly', function () {
        $contract = Contract::factory()->create([
            'start_date' => '2024-01-15',
            'end_date' => '2024-12-31',
        ]);

        expect($contract->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($contract->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('calculates monthly value for monthly billing', function () {
        $contract = Contract::factory()->create([
            'value' => 5000.00,
            'billing_frequency' => BillingFrequency::Monthly,
        ]);

        expect($contract->monthlyValue())->toBe(5000.00);
    });

    it('calculates monthly value for quarterly billing', function () {
        $contract = Contract::factory()->create([
            'value' => 15000.00,
            'billing_frequency' => BillingFrequency::Quarterly,
        ]);

        expect($contract->monthlyValue())->toBe(5000.00);
    });

    it('calculates monthly value for annual billing', function () {
        $contract = Contract::factory()->create([
            'value' => 120000.00,
            'billing_frequency' => BillingFrequency::Annual,
        ]);

        expect($contract->monthlyValue())->toBe(10000.00);
    });

    it('returns zero monthly value for one-time billing', function () {
        $contract = Contract::factory()->create([
            'value' => 50000.00,
            'billing_frequency' => BillingFrequency::OneTime,
        ]);

        expect($contract->monthlyValue())->toBe(0.0);
    });

    it('calculates weighted value based on probability', function () {
        $contract = Contract::factory()->create([
            'value' => 10000.00,
            'probability' => 50,
        ]);

        expect($contract->weightedValue())->toBe(5000.00);
    });

    it('returns full value when probability is 100', function () {
        $contract = Contract::factory()->create([
            'value' => 10000.00,
            'probability' => 100,
        ]);

        expect($contract->weightedValue())->toBe(10000.00);
    });
});

describe('Contract Scopes', function () {
    it('filters active contracts', function () {
        Contract::factory()->create([
            'status' => ContractStatus::Confirmed,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        Contract::factory()->create([
            'status' => ContractStatus::Cancelled,
            'start_date' => now()->subMonth(),
        ]);

        Contract::factory()->create([
            'status' => ContractStatus::Confirmed,
            'start_date' => now()->addMonth(),
        ]);

        expect(Contract::active()->count())->toBe(1);
    });

    it('filters confirmed contracts', function () {
        Contract::factory()->confirmed()->create();
        Contract::factory()->pipeline()->create();
        Contract::factory()->completed()->create();

        expect(Contract::confirmed()->count())->toBe(1);
    });

    it('filters pipeline contracts', function () {
        Contract::factory()->confirmed()->create();
        Contract::factory()->pipeline()->create();
        Contract::factory()->pipeline()->create();

        expect(Contract::pipeline()->count())->toBe(2);
    });
});
