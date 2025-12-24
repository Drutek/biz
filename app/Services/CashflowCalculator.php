<?php

namespace App\Services;

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Setting;
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

    public function cashBalance(): float
    {
        return (float) Setting::get(Setting::KEY_CASH_BALANCE, 0);
    }

    public function runway(): float
    {
        $cashBalance = $this->cashBalance();
        $monthlyIncome = $this->monthlyConfirmedIncome();
        $monthlyBurn = $this->monthlyBurn();
        $netMonthly = $monthlyIncome - $monthlyBurn;

        // If making money or breaking even, runway is infinite
        if ($netMonthly >= 0) {
            return INF;
        }

        // If no cash balance set, can't calculate runway
        if ($cashBalance <= 0) {
            return 0.0;
        }

        // Runway = cash / monthly burn rate (net loss)
        $monthlyNetBurn = abs($netMonthly);

        return $cashBalance / $monthlyNetBurn;
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
     *     cash_balance: float,
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
            'cash_balance' => $this->cashBalance(),
            'monthly_income' => $income,
            'monthly_expenses' => $expenses,
            'monthly_pipeline' => $pipeline,
            'monthly_net' => $income - $expenses,
            'runway_months' => $this->runway(),
        ];
    }
}
