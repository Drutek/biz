<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Market News</flux:heading>
        <flux:button variant="ghost" wire:click="markAllAsRead">
            Mark All as Read
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap gap-4">
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search news..." />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="entityFilter">
                <option value="">All Entities</option>
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-36">
            <flux:select wire:model.live="readFilter">
                <option value="">All</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </flux:select>
        </div>
        <div class="flex items-center">
            <flux:checkbox wire:model.live="showDismissed" label="Show Dismissed" />
        </div>
    </div>

    <div class="space-y-4">
        @forelse($newsItems as $item)
            <div wire:key="news-{{ $item->id }}"
                 class="rounded-lg border p-4 {{ $item->is_read ? 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50' : 'border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-800' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <flux:badge size="sm" color="blue">
                                {{ $item->trackedEntity->name }}
                            </flux:badge>
                            @if(!$item->is_read)
                                <flux:badge size="sm" color="green">New</flux:badge>
                            @endif
                            @if(!$item->is_relevant)
                                <flux:badge size="sm" color="zinc">Dismissed</flux:badge>
                            @endif
                        </div>
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                            <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                               class="hover:text-blue-600 dark:hover:text-blue-400"
                               wire:click="markAsRead({{ $item->id }})">
                                {{ $item->title }}
                            </a>
                        </h3>
                        @if($item->snippet)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">
                                {{ $item->snippet }}
                            </p>
                        @endif
                        <div class="mt-2 flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-500">
                            <span>{{ $item->source }}</span>
                            <span>{{ $item->fetched_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        @if(!$item->is_read)
                            <flux:button size="sm" variant="ghost" wire:click="markAsRead({{ $item->id }})">
                                Mark Read
                            </flux:button>
                        @endif
                        @if($item->is_relevant)
                            <flux:button size="sm" variant="ghost" wire:click="dismiss({{ $item->id }})">
                                Dismiss
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-zinc-500 dark:text-zinc-400">
                No news items found. Add tracked entities and wait for news to be fetched.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $newsItems->links() }}
    </div>
</div>
