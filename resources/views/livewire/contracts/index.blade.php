<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Contracts</flux:heading>
        <flux:button variant="primary" wire:click="create">
            Add Contract
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Frequency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Period</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($contracts as $contract)
                    <tr wire:key="contract-{{ $contract->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $contract->name }}</div>
                            @if($contract->description)
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($contract->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ number_format($contract->value, 2) }}
                            @if($contract->status->value === 'pipeline')
                                <span class="text-zinc-500">({{ $contract->probability }}%)</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $contract->billing_frequency->label() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="$contract->status->color()">
                                {{ $contract->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $contract->start_date->format('M j, Y') }}
                            @if($contract->end_date)
                                - {{ $contract->end_date->format('M j, Y') }}
                            @else
                                - Ongoing
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $contract->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $contract->id }})" wire:confirm="Are you sure you want to delete this contract?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No contracts yet. Click "Add Contract" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="contract-form" wire:model.self="showForm" @close="closeForm">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                {{ $editingContract ? 'Edit Contract' : 'Add Contract' }}
            </flux:heading>
            <livewire:contracts.contract-form :contract="$editingContract" :key="$editingContract?->id ?? 'new'" />
        </div>
    </flux:modal>
</div>
