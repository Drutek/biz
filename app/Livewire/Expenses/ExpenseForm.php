<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseFrequency;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ExpenseForm extends Component
{
    public ?Expense $expense = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $description = null;

    #[Validate('required|numeric|min:0')]
    public string|float $amount = '';

    #[Validate('required|string')]
    public string $frequency = '';

    #[Validate('required|string|max:100')]
    public string $category = '';

    #[Validate('required|date')]
    public string $start_date = '';

    public ?string $end_date = null;

    public bool $is_active = true;

    private const CATEGORIES = ['software', 'professional', 'office', 'tax', 'marketing', 'travel', 'equipment', 'other'];

    public function mount(?Expense $expense = null): void
    {
        if ($expense?->exists) {
            $this->expense = $expense;
            $this->name = $expense->name;
            $this->description = $expense->description;
            $this->amount = (string) $expense->amount;
            $this->frequency = $expense->frequency->value;
            $this->category = $expense->category;
            $this->start_date = $expense->start_date->format('Y-m-d');
            $this->end_date = $expense->end_date?->format('Y-m-d');
            $this->is_active = $expense->is_active;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'category' => $this->category,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->expense?->exists) {
            $this->expense->update($data);
        } else {
            Expense::create($data);
        }

        $this->dispatch('expense-saved');
        $this->dispatch('close-modal');
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-form', [
            'frequencies' => ExpenseFrequency::cases(),
            'categories' => self::CATEGORIES,
        ]);
    }
}
