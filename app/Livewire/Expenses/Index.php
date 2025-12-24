<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?Expense $editingExpense = null;

    public function render(): View
    {
        return view('livewire.expenses.index', [
            'expenses' => Expense::query()
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function create(): void
    {
        $this->editingExpense = null;
        $this->showForm = true;
    }

    public function edit(Expense $expense): void
    {
        $this->editingExpense = $expense;
        $this->showForm = true;
    }

    public function delete(int $expenseId): void
    {
        Expense::destroy($expenseId);
    }

    #[On('close-modal')]
    #[On('expense-saved')]
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingExpense = null;
    }
}
