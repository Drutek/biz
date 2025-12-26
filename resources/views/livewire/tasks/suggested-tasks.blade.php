<div class="space-y-3">
    @if($suggestedTasks->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon.sparkles class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
            <h3 class="mt-3 font-medium text-zinc-900 dark:text-white">No task suggestions</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                AI-suggested tasks will appear here based on insights and your standups.
            </p>
        </div>
    @else
        @foreach($suggestedTasks as $task)
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20" wire:key="suggested-{{ $task->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:icon.light-bulb class="h-5 w-5 text-blue-500" />
                            <h3 class="font-medium text-zinc-900 dark:text-white">{{ $task->title }}</h3>
                            <flux:badge size="sm" :color="$task->priority->color()">
                                {{ $task->priority->label() }}
                            </flux:badge>
                        </div>
                        @if($task->description)
                            <p class="mt-1 ml-7 text-sm text-zinc-600 dark:text-zinc-400">{{ $task->description }}</p>
                        @endif
                        @if($task->due_date)
                            <p class="mt-2 ml-7 text-xs text-zinc-400 dark:text-zinc-500">
                                <flux:icon.calendar class="inline h-3 w-3 mr-1" />
                                Suggested due: {{ $task->due_date->format('M j, Y') }}
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($rejectingTaskId === $task->id)
                            <div class="flex items-center gap-2">
                                <flux:input
                                    wire:model="rejectionReason"
                                    placeholder="Reason (optional)"
                                    size="sm"
                                />
                                <flux:button size="sm" variant="danger" wire:click="rejectTask">
                                    Reject
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="cancelReject">
                                    Cancel
                                </flux:button>
                            </div>
                        @else
                            <flux:button size="sm" variant="primary" wire:click="acceptTask({{ $task->id }})">
                                Accept
                            </flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="showRejectForm({{ $task->id }})">
                                Reject
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
