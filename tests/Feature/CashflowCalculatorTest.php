<?php

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Enums\ExpenseFrequency;
use App\Models\Contract;
use App\Models\Expense;
use App\Services\CashflowCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new CashflowCalculator;
});

describe('Monthly Burn Rate', function () {
    it('calculates total monthly expenses', function () {
        Expense::factory()->monthly(1000)->active()->create();
        Expense::factory()->monthly(500)->active()->create();
        Expense::factory()->quarterly(600)->active()->create();

        expect($this->calculator->monthlyBurn())->toBe(1700.0);
    });

    it('ignores inactive expenses', function () {
        Expense::factory()->monthly(1000)->active()->create();
        Expense::factory()->monthly(500)->inactive()->create();

        expect($this->calculator->monthlyBurn())->toBe(1000.0);
    });

    it('ignores one-time expenses', function () {
        Expense::factory()->monthly(1000)->active()->create();
        Expense::factory()->oneTime(5000)->active()->create();

        expect($this->calculator->monthlyBurn())->toBe(1000.0);
    });

    it('returns zero with no expenses', function () {
        expect($this->calculator->monthlyBurn())->toBe(0.0);
    });
});

describe('Monthly Confirmed Income', function () {
    it('calculates total monthly confirmed income', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Contract::factory()->confirmed()->quarterly(3000)->active()->create();

        expect($this->calculator->monthlyConfirmedIncome())->toBe(6000.0);
    });

    it('ignores pipeline contracts', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Contract::factory()->pipeline()->monthly(3000)->active()->create();

        expect($this->calculator->monthlyConfirmedIncome())->toBe(5000.0);
    });

    it('ignores inactive contracts', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Contract::factory()->confirmed()->monthly(3000)->create([
            'start_date' => now()->addMonth(),
        ]);

        expect($this->calculator->monthlyConfirmedIncome())->toBe(5000.0);
    });

    it('returns zero with no contracts', function () {
        expect($this->calculator->monthlyConfirmedIncome())->toBe(0.0);
    });
});

describe('Monthly Pipeline Income', function () {
    it('calculates weighted monthly pipeline income', function () {
        Contract::factory()->pipeline()->monthly(10000)->active()->create([
            'probability' => 50,
        ]);

        expect($this->calculator->monthlyPipelineIncome())->toBe(5000.0);
    });

    it('ignores confirmed contracts', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Contract::factory()->pipeline()->monthly(10000)->active()->create([
            'probability' => 50,
        ]);

        expect($this->calculator->monthlyPipelineIncome())->toBe(5000.0);
    });

    it('returns zero with no pipeline contracts', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();

        expect($this->calculator->monthlyPipelineIncome())->toBe(0.0);
    });
});

describe('Runway Calculation', function () {
    it('calculates months of runway', function () {
        Contract::factory()->confirmed()->monthly(10000)->active()->create();
        Expense::factory()->monthly(2000)->active()->create();

        expect($this->calculator->runway())->toBe(INF);
    });

    it('returns zero runway when expenses exceed income', function () {
        Contract::factory()->confirmed()->monthly(1000)->active()->create();
        Expense::factory()->monthly(2000)->active()->create();

        $runway = $this->calculator->runway();

        expect($runway)->toBe(0.0);
    });

    it('returns infinity when no expenses', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();

        expect($this->calculator->runway())->toBe(INF);
    });

    it('returns zero runway when no income', function () {
        Expense::factory()->monthly(2000)->active()->create();

        expect($this->calculator->runway())->toBe(0.0);
    });
});

describe('Cashflow Projections', function () {
    it('returns monthly projections for specified months', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Expense::factory()->monthly(2000)->active()->create();

        $projections = $this->calculator->project(6);

        expect($projections)->toHaveCount(6)
            ->and($projections[0])->toHaveKeys(['month', 'income', 'expenses', 'net', 'cumulative']);
    });

    it('calculates correct net for each month', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Expense::factory()->monthly(2000)->active()->create();

        $projections = $this->calculator->project(3);

        expect($projections[0]['income'])->toBe(5000.0)
            ->and($projections[0]['expenses'])->toBe(2000.0)
            ->and($projections[0]['net'])->toBe(3000.0);
    });

    it('calculates cumulative correctly', function () {
        Contract::factory()->confirmed()->monthly(5000)->active()->create();
        Expense::factory()->monthly(2000)->active()->create();

        $projections = $this->calculator->project(3);

        expect($projections[0]['cumulative'])->toBe(3000.0)
            ->and($projections[1]['cumulative'])->toBe(6000.0)
            ->and($projections[2]['cumulative'])->toBe(9000.0);
    });

    it('includes one-time income in appropriate month', function () {
        Contract::factory()->confirmed()->oneTime(10000)->create([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $projections = $this->calculator->project(3);

        expect($projections[0]['income'])->toBe(10000.0)
            ->and($projections[1]['income'])->toBe(0.0);
    });

    it('handles contracts with end dates', function () {
        Contract::factory()->confirmed()->monthly(5000)->create([
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $projections = $this->calculator->project(3);

        expect($projections[0]['income'])->toBe(5000.0)
            ->and($projections[1]['income'])->toBe(5000.0)
            ->and($projections[2]['income'])->toBe(0.0);
    });
});
