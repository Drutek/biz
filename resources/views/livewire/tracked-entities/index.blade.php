<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Tracked Entities</flux:heading>
        <flux:button variant="primary" wire:click="create">
            Add Entity
        </flux:button>
    </div>

    <div class="mb-4 flex gap-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search entities..." />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="typeFilter">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Search Query</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">News Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($entities as $entity)
                    <tr wire:key="entity-{{ $entity->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $entity->name }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="$entity->entity_type->color()">
                                {{ $entity->entity_type->label() }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $entity->search_query ? Str::limit($entity->search_query, 30) : '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $entity->news_items_count }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($entity->is_active)
                                <flux:badge color="green">Active</flux:badge>
                            @else
                                <flux:badge color="zinc">Inactive</flux:badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $entity->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $entity->id }})" wire:confirm="Are you sure you want to delete this entity?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No tracked entities yet. Click "Add Entity" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="entity-form" wire:model.self="showForm" @close="closeForm">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                {{ $editingEntity ? 'Edit Entity' : 'Add Entity' }}
            </flux:heading>
            <livewire:tracked-entities.tracked-entity-form :key="$editingEntity?->id ?? 'new'" />
        </div>
    </flux:modal>
</div>
