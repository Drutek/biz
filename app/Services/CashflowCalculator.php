<?php

namespace App\Services;

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashflowCalculator
{
    public function monthlyBurn(): float
    {
        return Expense::active()
            ->recurring()
            ->get()
            ->sum(fn (Expense $expense) => $expense->monthlyAmount());
    }

    public function monthlyConfirmedIncome(): float
    {
        return Contract::active()
            ->confirmed()
            ->get()
            ->sum(fn (Contract $contract) => $contract->monthlyValue());
    }

    public function monthlyPipelineIncome(): float
    {
        return Contract::active()
            ->pipeline()
            ->get()
            ->sum(fn (Contract $contract) => $contract->weightedMonthlyValue());
    }

    public function runway(): float
    {
        $monthlyIncome = $this->monthlyConfirmedIncome();
        $monthlyBurn = $this->monthlyBurn();

        if ($monthlyBurn <= 0) {
            return INF;
        }

        $netMonthly = $monthlyIncome - $monthlyBurn;

        if ($netMonthly >= 0) {
            return INF;
        }

        return 0.0;
    }

    /**
     * Generate monthly cashflow projections.
     *
     * @return array<int, array{month: string, income: float, expenses: float, net: float, cumulative: float}>
     */
    public function project(int $months = 12): array
    {
        $projections = [];
        $cumulative = 0.0;
        $startOfMonth = now()->startOfMonth();

        $contracts = Contract::query()
            ->whereIn('status', [ContractStatus::Confirmed, ContractStatus::Pipeline])
            ->get();

        $expenses = Expense::active()->recurring()->get();

        for ($i = 0; $i < $months; $i++) {
            $month = $startOfMonth->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');

            $income = $this->calculateIncomeForMonth($contracts, $month);
            $expenseTotal = $this->calculateExpensesForMonth($expenses, $month);
            $net = $income - $expenseTotal;
            $cumulative += $net;

            $projections[] = [
                'month' => $monthKey,
                'income' => $income,
                'expenses' => $expenseTotal,
                'net' => $net,
                'cumulative' => $cumulative,
            ];
        }

        return $projections;
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     */
    private function calculateIncomeForMonth(Collection $contracts, Carbon $month): float
    {
        $total = 0.0;
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        foreach ($contracts as $contract) {
            if ($contract->status !== ContractStatus::Confirmed) {
                continue;
            }

            if ($contract->start_date > $monthEnd) {
                continue;
            }

            if ($contract->end_date && $contract->end_date < $monthStart) {
                continue;
            }

            if ($contract->billing_frequency === BillingFrequency::OneTime) {
                if ($contract->start_date >= $monthStart && $contract->start_date <= $monthEnd) {
                    $total += (float) $contract->value;
                }
            } else {
                $total += $contract->monthlyValue();
            }
        }

        return $total;
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     */
    private function calculateExpensesForMonth(Collection $expenses, Carbon $month): float
    {
        $total = 0.0;
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        foreach ($expenses as $expense) {
            if ($expense->start_date > $monthEnd) {
                continue;
            }

            if ($expense->end_date && $expense->end_date < $monthStart) {
                continue;
            }

            $total += $expense->monthlyAmount();
        }

        return $total;
    }

    /**
     * Get summary statistics for the dashboard.
     *
     * @return array{
     *     monthly_income: float,
     *     monthly_expenses: float,
     *     monthly_pipeline: float,
     *     monthly_net: float,
     *     runway_months: float
     * }
     */
    public function summary(): array
    {
        $income = $this->monthlyConfirmedIncome();
        $expenses = $this->monthlyBurn();
        $pipeline = $this->monthlyPipelineIncome();

        return [
            'monthly_income' => $income,
            'monthly_expenses' => $expenses,
            'monthly_pipeline' => $pipeline,
            'monthly_net' => $income - $expenses,
            'runway_months' => $this->runway(),
        ];
    }
}
