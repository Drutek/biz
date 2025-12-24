<div class="relative">
    <flux:button
        wire:click="toggleDropdown"
        variant="ghost"
        size="sm"
        class="relative"
    >
        <flux:icon.bell class="h-5 w-5" />
        @if($this->unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </flux:button>

    @if($showDropdown)
        <div
            class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
            @click.away="$wire.toggleDropdown()"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <span class="font-semibold text-zinc-900 dark:text-white">Notifications</span>
                @if($this->unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Mark all read
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse($this->recentNotifications as $notification)
                    <div
                        wire:key="notification-{{ $notification->id }}"
                        class="border-b border-zinc-100 px-4 py-3 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/50"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex-1">
                                @if(isset($notification->data['title']))
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        {{ $notification->data['title'] }}
                                    </p>
                                @endif
                                @if(isset($notification->data['description']))
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ Str::limit($notification->data['description'], 60) }}
                                    </p>
                                @elseif(isset($notification->data['content_preview']))
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ Str::limit($notification->data['content_preview'], 60) }}
                                    </p>
                                @endif
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <button
                                wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                title="Mark as read"
                            >
                                <flux:icon.check class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-zinc-500">
                        No new notifications
                    </div>
                @endforelse
            </div>

            <div class="border-t border-zinc-200 px-4 py-2 dark:border-zinc-700">
                <a
                    href="{{ route('notifications.index') }}"
                    wire:navigate
                    class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    View all notifications
                </a>
            </div>
        </div>
    @endif
</div>
