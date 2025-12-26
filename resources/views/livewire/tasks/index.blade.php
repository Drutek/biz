<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Tasks</flux:heading>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Manage your suggested and active tasks
            </p>
        </div>
        @if($hasIntegration)
            <flux:badge color="green" size="sm">
                <flux:icon.link class="h-3 w-3 mr-1" />
                {{ $integrationName }} connected
            </flux:badge>
        @else
            <a href="{{ route('settings.integrations') }}" wire:navigate>
                <flux:button size="sm" variant="ghost">
                    <flux:icon.link class="h-4 w-4 mr-1" />
                    Connect task manager
                </flux:button>
            </a>
        @endif
    </div>

    {{-- Export Messages --}}
    @if($exportSuccess)
        <flux:callout variant="success" class="flex items-center justify-between">
            <span>{{ $exportSuccess }}</span>
            <flux:button size="sm" variant="ghost" wire:click="$set('exportSuccess', null)">Dismiss</flux:button>
        </flux:callout>
    @endif

    @if($exportError)
        <flux:callout variant="danger" class="flex items-center justify-between">
            <span>{{ $exportError }}</span>
            <flux:button size="sm" variant="ghost" wire:click="$set('exportError', null)">Dismiss</flux:button>
        </flux:callout>
    @endif

    {{-- Filter Tabs --}}
    <div class="flex gap-2">
        <flux:button
            wire:click="setStatusFilter('active')"
            :variant="$statusFilter === 'active' ? 'primary' : 'ghost'"
            size="sm"
        >
            Active ({{ $counts['active'] }})
        </flux:button>
        <flux:button
            wire:click="setStatusFilter('suggested')"
            :variant="$statusFilter === 'suggested' ? 'primary' : 'ghost'"
            size="sm"
        >
            Suggested ({{ $counts['suggested'] }})
        </flux:button>
        <flux:button
            wire:click="setStatusFilter('completed')"
            :variant="$statusFilter === 'completed' ? 'primary' : 'ghost'"
            size="sm"
        >
            Completed ({{ $counts['completed'] }})
        </flux:button>
    </div>

    {{-- Suggested Tasks Component (when on suggested filter) --}}
    @if($statusFilter === 'suggested')
        <livewire:tasks.suggested-tasks />
    @else
        {{-- Tasks List --}}
        @if($tasks->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.clipboard-document-list class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <h3 class="mt-4 font-medium text-zinc-900 dark:text-white">No tasks</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($statusFilter === 'active')
                        Accept suggested tasks to add them here.
                    @else
                        Complete tasks to see them here.
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($tasks as $task)
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800" wire:key="task-{{ $task->id }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ $task->title }}</h3>
                                    <flux:badge size="sm" :color="$task->priority->color()">
                                        {{ $task->priority->label() }}
                                    </flux:badge>
                                    <flux:badge size="sm" :color="$task->status->color()">
                                        {{ $task->status->label() }}
                                    </flux:badge>
                                </div>
                                @if($task->description)
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $task->description }}</p>
                                @endif
                                <div class="mt-2 flex items-center gap-4 text-xs text-zinc-400 dark:text-zinc-500">
                                    @if($task->due_date)
                                        <span class="{{ $task->isOverdue() ? 'text-red-500' : '' }}">
                                            <flux:icon.calendar class="inline h-3 w-3 mr-1" />
                                            {{ $task->due_date->format('M j, Y') }}
                                            @if($task->isOverdue())
                                                ({{ $task->daysOverdue() }} days overdue)
                                            @endif
                                        </span>
                                    @endif
                                    <span>
                                        <flux:icon.{{ $task->source->icon() }} class="inline h-3 w-3 mr-1" />
                                        {{ $task->source->label() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($task->status === \App\Enums\TaskStatus::Accepted)
                                    <flux:button size="sm" wire:click="startTask({{ $task->id }})">
                                        Start
                                    </flux:button>
                                @endif
                                @if($task->status === \App\Enums\TaskStatus::InProgress)
                                    <flux:button size="sm" variant="primary" wire:click="completeTask({{ $task->id }})">
                                        Complete
                                    </flux:button>
                                @endif
                                @if($hasIntegration && !$task->isExported() && $task->status->isActionable())
                                    <flux:button size="sm" variant="ghost" wire:click="exportTask({{ $task->id }})" wire:loading.attr="disabled">
                                        <flux:icon.arrow-up-tray class="h-4 w-4 mr-1" />
                                        <span wire:loading.remove wire:target="exportTask({{ $task->id }})">Export</span>
                                        <span wire:loading wire:target="exportTask({{ $task->id }})">...</span>
                                    </flux:button>
                                @elseif($task->isExported())
                                    @if($task->getExternalUrl())
                                        <a href="{{ $task->getExternalUrl() }}" target="_blank" rel="noopener noreferrer">
                                            <flux:button size="sm" variant="ghost">
                                                <flux:icon.arrow-top-right-on-square class="h-4 w-4 mr-1" />
                                                View in {{ ucfirst($task->getExportedProvider()) }}
                                            </flux:button>
                                        </a>
                                    @else
                                        <flux:badge size="sm" color="zinc">
                                            Exported to {{ ucfirst($task->getExportedProvider()) }}
                                        </flux:badge>
                                    @endif
                                @endif
                                @if($task->status->isActionable())
                                    <flux:button size="sm" variant="ghost" wire:click="cancelTask({{ $task->id }})">
                                        Cancel
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
