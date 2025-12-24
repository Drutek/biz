<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Notifications</flux:heading>
        <div class="flex items-center gap-3">
            @if($this->unreadCount > 0)
                <flux:button wire:click="markAllAsRead" variant="ghost" size="sm">
                    Mark all as read
                </flux:button>
            @endif
            <flux:button wire:click="deleteAll" variant="ghost" size="sm" class="text-red-600 hover:text-red-700 dark:text-red-400">
                Delete all
            </flux:button>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <flux:button
            wire:click="$set('filter', 'all')"
            :variant="$filter === 'all' ? 'primary' : 'ghost'"
            size="sm"
        >
            All
        </flux:button>
        <flux:button
            wire:click="$set('filter', 'unread')"
            :variant="$filter === 'unread' ? 'primary' : 'ghost'"
            size="sm"
        >
            Unread
            @if($this->unreadCount > 0)
                <flux:badge size="sm" color="red" class="ml-1">{{ $this->unreadCount }}</flux:badge>
            @endif
        </flux:button>
        <flux:button
            wire:click="$set('filter', 'read')"
            :variant="$filter === 'read' ? 'primary' : 'ghost'"
            size="sm"
        >
            Read
        </flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @forelse($this->notifications as $notification)
            <div
                wire:key="notification-{{ $notification->id }}"
                class="flex items-start gap-4 border-b border-zinc-100 p-4 last:border-b-0 dark:border-zinc-700 {{ is_null($notification->read_at) ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
            >
                <div class="mt-1 flex-shrink-0">
                    @php
                        $type = $notification->data['type'] ?? 'default';
                        $iconClass = match($type) {
                            'proactive_insight' => 'text-purple-500',
                            'business_event' => 'text-blue-500',
                            'runway_alert' => 'text-red-500',
                            'daily_standup' => 'text-green-500',
                            default => 'text-zinc-400',
                        };
                    @endphp
                    @switch($type)
                        @case('proactive_insight')
                            <flux:icon.light-bulb class="h-5 w-5 {{ $iconClass }}" />
                            @break
                        @case('business_event')
                            <flux:icon.calendar class="h-5 w-5 {{ $iconClass }}" />
                            @break
                        @case('runway_alert')
                            <flux:icon.exclamation-triangle class="h-5 w-5 {{ $iconClass }}" />
                            @break
                        @case('daily_standup')
                            <flux:icon.newspaper class="h-5 w-5 {{ $iconClass }}" />
                            @break
                        @default
                            <flux:icon.bell class="h-5 w-5 {{ $iconClass }}" />
                    @endswitch
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            @if(isset($notification->data['title']))
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $notification->data['title'] }}
                                </p>
                            @endif
                            @if(isset($notification->data['description']))
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $notification->data['description'] }}
                                </p>
                            @elseif(isset($notification->data['content_preview']))
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ Str::limit($notification->data['content_preview'], 150) }}
                                </p>
                            @endif
                        </div>

                        @if(is_null($notification->read_at))
                            <span class="h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></span>
                        @endif
                    </div>

                    <div class="mt-2 flex items-center gap-4 text-xs text-zinc-500">
                        <span>{{ $notification->created_at->diffForHumans() }}</span>

                        @if(isset($notification->data['priority']))
                            <flux:badge size="sm" :color="match($notification->data['priority']) {
                                'urgent' => 'red',
                                'high' => 'amber',
                                'medium' => 'blue',
                                default => 'zinc',
                            }">
                                {{ ucfirst($notification->data['priority']) }}
                            </flux:badge>
                        @endif

                        @if(isset($notification->data['significance']))
                            <flux:badge size="sm" :color="match($notification->data['significance']) {
                                'critical' => 'red',
                                'high' => 'amber',
                                'medium' => 'blue',
                                default => 'zinc',
                            }">
                                {{ ucfirst($notification->data['significance']) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(is_null($notification->read_at))
                        <flux:button
                            wire:click="markAsRead('{{ $notification->id }}')"
                            variant="ghost"
                            size="sm"
                            title="Mark as read"
                        >
                            <flux:icon.check class="h-4 w-4" />
                        </flux:button>
                    @else
                        <flux:button
                            wire:click="markAsUnread('{{ $notification->id }}')"
                            variant="ghost"
                            size="sm"
                            title="Mark as unread"
                        >
                            <flux:icon.envelope class="h-4 w-4" />
                        </flux:button>
                    @endif

                    <flux:button
                        wire:click="delete('{{ $notification->id }}')"
                        variant="ghost"
                        size="sm"
                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                        title="Delete"
                    >
                        <flux:icon.trash class="h-4 w-4" />
                    </flux:button>
                </div>
            </div>
        @empty
            <div class="px-4 py-12 text-center">
                <flux:icon.bell class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-4 text-zinc-500 dark:text-zinc-400">
                    @if($filter === 'unread')
                        No unread notifications
                    @elseif($filter === 'read')
                        No read notifications
                    @else
                        No notifications yet
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    @if($this->notifications->hasPages())
        <div class="mt-4">
            {{ $this->notifications->links() }}
        </div>
    @endif
</div>
