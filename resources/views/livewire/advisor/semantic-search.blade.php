<div class="space-y-4">
    <div class="relative">
        <flux:input
            wire:model.live.debounce.300ms="query"
            placeholder="Search past conversations..."
            type="search"
        />
        @if($query)
            <button
                wire:click="clear"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
            >
                <flux:icon name="x-mark" class="h-4 w-4" />
            </button>
        @endif
    </div>

    @if(strlen($query) >= 3)
        <div class="max-h-96 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            @if($isSearching)
                <div class="flex items-center justify-center p-8">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Searching...</span>
                    </div>
                </div>
            @elseif($results->isEmpty())
                <div class="p-8 text-center text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="magnifying-glass" class="mx-auto h-8 w-8 mb-2 opacity-50" />
                    <p>No matching conversations found.</p>
                    <p class="text-sm mt-1">Try different keywords or phrases.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($results as $result)
                        <div
                            wire:key="result-{{ $result->id }}"
                            class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-colors"
                            wire:click="$dispatch('navigate-to-thread', { threadId: {{ $result->advisory_thread_id }} })"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $result->role === 'user' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                            {{ ucfirst($result->role) }}
                                        </span>
                                        @if($result->thread_title)
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                                {{ $result->thread_title }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300 line-clamp-3">
                                        {{ Str::limit($result->content, 200) }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ \Carbon\Carbon::parse($result->created_at)->format('M j, Y') }}
                                    </span>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400" title="Similarity score">
                                            {{ number_format((1 - $result->distance) * 100, 0) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @elseif(strlen($query) > 0)
        <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center">
            Type at least 3 characters to search...
        </p>
    @endif
</div>
