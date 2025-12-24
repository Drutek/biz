<?php

use App\Enums\ExpenseFrequency;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Expense Model', function () {
    it('can be created with valid data', function () {
        $expense = Expense::factory()->create([
            'name' => 'Office Rent',
            'amount' => 2000.00,
            'frequency' => ExpenseFrequency::Monthly,
            'category' => 'office',
        ]);

        expect($expense)->toBeInstanceOf(Expense::class)
            ->and($expense->name)->toBe('Office Rent')
            ->and((float) $expense->amount)->toBe(2000.00)
            ->and($expense->frequency)->toBe(ExpenseFrequency::Monthly)
            ->and($expense->category)->toBe('office');
    });

    it('casts dates correctly', function () {
        $expense = Expense::factory()->create([
            'start_date' => '2024-01-15',
            'end_date' => '2024-12-31',
        ]);

        expect($expense->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($expense->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('calculates monthly amount for monthly frequency', function () {
        $expense = Expense::factory()->create([
            'amount' => 500.00,
            'frequency' => ExpenseFrequency::Monthly,
        ]);

        expect($expense->monthlyAmount())->toBe(500.00);
    });

    it('calculates monthly amount for quarterly frequency', function () {
        $expense = Expense::factory()->create([
            'amount' => 1500.00,
            'frequency' => ExpenseFrequency::Quarterly,
        ]);

        expect($expense->monthlyAmount())->toBe(500.00);
    });

    it('calculates monthly amount for annual frequency', function () {
        $expense = Expense::factory()->create([
            'amount' => 12000.00,
            'frequency' => ExpenseFrequency::Annual,
        ]);

        expect($expense->monthlyAmount())->toBe(1000.00);
    });

    it('returns zero monthly amount for one-time expenses', function () {
        $expense = Expense::factory()->create([
            'amount' => 5000.00,
            'frequency' => ExpenseFrequency::OneTime,
        ]);

        expect($expense->monthlyAmount())->toBe(0.0);
    });
});

describe('Expense Scopes', function () {
    it('filters active expenses', function () {
        Expense::factory()->create([
            'is_active' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        Expense::factory()->create([
            'is_active' => false,
            'start_date' => now()->subMonth(),
        ]);

        Expense::factory()->create([
            'is_active' => true,
            'start_date' => now()->addMonth(),
        ]);

        expect(Expense::active()->count())->toBe(1);
    });

    it('filters recurring expenses', function () {
        Expense::factory()->monthly()->create();
        Expense::factory()->quarterly()->create();
        Expense::factory()->oneTime()->create();

        expect(Expense::recurring()->count())->toBe(2);
    });

    it('filters by category', function () {
        Expense::factory()->create(['category' => 'software']);
        Expense::factory()->create(['category' => 'software']);
        Expense::factory()->create(['category' => 'office']);

        expect(Expense::byCategory('software')->count())->toBe(2);
    });
});
