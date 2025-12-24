<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Expenses</flux:heading>
        <flux:button variant="primary" wire:click="create">
            Add Expense
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Frequency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($expenses as $expense)
                    <tr wire:key="expense-{{ $expense->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $expense->name }}</div>
                            @if($expense->description)
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($expense->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ number_format($expense->amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $expense->frequency->label() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge>{{ ucfirst($expense->category) }}</flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($expense->is_active)
                                <flux:badge color="green">Active</flux:badge>
                            @else
                                <flux:badge color="zinc">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $expense->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $expense->id }})" wire:confirm="Are you sure you want to delete this expense?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No expenses yet. Click "Add Expense" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="expense-form" wire:model.self="showForm" @close="closeForm">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                {{ $editingExpense ? 'Edit Expense' : 'Add Expense' }}
            </flux:heading>
            <livewire:expenses.expense-form :expense="$editingExpense" :key="$editingExpense?->id ?? 'new'" />
        </div>
    </flux:modal>
</div>
