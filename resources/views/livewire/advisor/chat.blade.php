<div class="flex h-[calc(100vh-8rem)] gap-4">
    {{-- Threads Sidebar --}}
    <div class="w-64 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="sm">Conversations</flux:heading>
            <div class="flex items-center gap-1">
                <flux:modal.trigger name="semantic-search">
                    <flux:button size="sm" variant="ghost" title="Search conversations">
                        <flux:icon name="magnifying-glass" class="h-4 w-4" />
                    </flux:button>
                </flux:modal.trigger>
                <flux:button size="sm" variant="primary" wire:click="newThread">New</flux:button>
            </div>
        </div>
        <div class="h-full overflow-y-auto p-2">
            @forelse($threads as $thread)
                <div wire:key="thread-{{ $thread->id }}"
                     class="group flex cursor-pointer items-center justify-between rounded-md p-2 {{ $currentThreadId === $thread->id ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-100 dark:hover:bg-zinc-700/50' }}"
                     wire:click="selectThread({{ $thread->id }})">
                    <span class="truncate text-sm text-zinc-700 dark:text-zinc-300">
                        {{ $thread->title }}
                    </span>
                    <flux:button size="xs"
                                 variant="ghost"
                                 class="opacity-0 group-hover:opacity-100"
                                 wire:click.stop="deleteThread({{ $thread->id }})"
                                 wire:confirm="Delete this conversation?">
                        <flux:icon name="x-mark" class="h-3 w-3" />
                    </flux:button>
                </div>
            @empty
                <p class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    No conversations yet.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Chat Area --}}
    <div class="flex flex-1 flex-col overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        @if($currentThread)
            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                @forelse($currentThread->messages as $msg)
                    <div wire:key="msg-{{ $msg->id }}"
                         class="flex {{ $msg->role->value === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="{{ $msg->role->value === 'user' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-100' }} max-w-[80%] rounded-lg px-4 py-3">
                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! \Illuminate\Support\Str::markdown($msg->content) !!}
                            </div>
                            <div class="mt-1 text-xs {{ $msg->role->value === 'user' ? 'text-blue-200' : 'text-zinc-400' }}">
                                {{ $msg->created_at->format('g:i A') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center text-zinc-500 dark:text-zinc-400">
                        <div class="text-center">
                            <p class="text-lg font-medium">Ask your business advisor</p>
                            <p class="text-sm">Get strategic advice based on your financial position and market news.</p>
                        </div>
                    </div>
                @endforelse

                <div wire:loading wire:target="sendMessage" class="flex justify-start">
                    <div class="bg-zinc-100 dark:bg-zinc-700 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Thinking...</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input --}}
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                <form wire:submit="sendMessage" class="flex gap-2">
                    <flux:input
                        wire:model="message"
                        placeholder="Ask about your business strategy..."
                        class="flex-1"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                    />
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="sendMessage">
                        <span wire:loading.remove wire:target="sendMessage">Send</span>
                        <span wire:loading wire:target="sendMessage" class="flex items-center gap-1">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending
                        </span>
                    </flux:button>
                </form>
                @error('message')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        @else
            {{-- Empty State --}}
            <div class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <flux:heading size="lg" class="mb-2">Business Advisor</flux:heading>
                    <p class="text-zinc-500 dark:text-zinc-400 mb-4">
                        Get AI-powered strategic advice based on your financial position and market news.
                    </p>
                    <flux:button variant="primary" wire:click="newThread">
                        Start New Conversation
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Semantic Search Modal --}}
    <flux:modal name="semantic-search" class="max-w-2xl">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">Search Conversations</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                Search your past conversations by meaning, not just keywords.
            </p>
            <livewire:advisor.semantic-search />
        </div>
    </flux:modal>
</div>

@script
<script>
    $wire.on('navigate-to-thread', (event) => {
        $wire.selectThread(event.threadId);
        Flux.close('semantic-search');
    });
</script>
@endscript
