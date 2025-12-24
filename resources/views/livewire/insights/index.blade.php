<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">AI Insights</flux:heading>
            @if($this->unreadCount > 0)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->unreadCount }} unread insight{{ $this->unreadCount > 1 ? 's' : '' }}
                </p>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4">
        <div class="w-48">
            <flux:select wire:model.live="type" placeholder="All Types">
                <flux:select.option value="">All Types</flux:select.option>
                @foreach($types as $t)
                    <flux:select.option value="{{ $t->value }}">{{ ucfirst($t->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="priority" placeholder="All Priorities">
                <flux:select.option value="">All Priorities</flux:select.option>
                @foreach($priorities as $p)
                    <flux:select.option value="{{ $p->value }}">{{ ucfirst($p->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="status">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="all">All</flux:select.option>
                <flux:select.option value="dismissed">Dismissed</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Insights List --}}
    <div class="space-y-4">
        @forelse($this->insights as $insight)
            <div
                wire:key="insight-{{ $insight->id }}"
                class="rounded-lg border p-4 {{ $insight->is_read ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' : 'border-blue-200 bg-blue-50/50 dark:border-blue-900/50 dark:bg-blue-900/10' }}"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        @php
                            $iconClass = match($insight->priority->value) {
                                'urgent' => 'text-red-500',
                                'high' => 'text-amber-500',
                                'medium' => 'text-blue-500',
                                default => 'text-zinc-400',
                            };
                        @endphp
                        @switch($insight->insight_type->value)
                            @case('opportunity')
                                <flux:icon.arrow-trending-up class="mt-1 h-6 w-6 {{ $iconClass }}" />
                                @break
                            @case('warning')
                                <flux:icon.exclamation-triangle class="mt-1 h-6 w-6 {{ $iconClass }}" />
                                @break
                            @case('recommendation')
                                <flux:icon.light-bulb class="mt-1 h-6 w-6 {{ $iconClass }}" />
                                @break
                            @case('analysis')
                                <flux:icon.chart-bar class="mt-1 h-6 w-6 {{ $iconClass }}" />
                                @break
                            @default
                                <flux:icon.sparkles class="mt-1 h-6 w-6 {{ $iconClass }}" />
                        @endswitch

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-medium text-zinc-900 dark:text-white">
                                    {{ $insight->title }}
                                </h4>
                                @if(!$insight->is_read)
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ Str::limit($insight->content, 300) }}
                            </p>
                            <div class="mt-2 flex items-center gap-3">
                                <flux:badge size="sm" :color="match($insight->priority->value) {
                                    'urgent' => 'red',
                                    'high' => 'amber',
                                    'medium' => 'blue',
                                    default => 'zinc',
                                }">
                                    {{ ucfirst($insight->priority->value) }}
                                </flux:badge>
                                <flux:badge size="sm" color="zinc">
                                    {{ ucfirst($insight->insight_type->value) }}
                                </flux:badge>
                                <span class="text-xs text-zinc-500">
                                    {{ $insight->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(!$insight->is_read)
                            <flux:button wire:click="markAsRead({{ $insight->id }})" variant="ghost" size="sm" title="Mark as read">
                                <flux:icon.check class="h-4 w-4" />
                            </flux:button>
                        @endif
                        @if(!$insight->is_dismissed)
                            <flux:button wire:click="dismiss({{ $insight->id }})" variant="ghost" size="sm" title="Dismiss" class="text-zinc-400 hover:text-zinc-600">
                                <flux:icon.x-mark class="h-4 w-4" />
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white px-4 py-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon.light-bulb class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <h3 class="mt-4 font-medium text-zinc-900 dark:text-white">No insights yet</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    AI-generated insights will appear here as they are created.
                </p>
            </div>
        @endforelse
    </div>

    @if($this->insights->hasPages())
        <div class="mt-4">
            {{ $this->insights->links() }}
        </div>
    @endif
</div>
