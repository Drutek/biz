<div>
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    LinkedIn Posts
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    AI-generated post suggestions for your LinkedIn profile
                </p>
            </div>
            <div class="flex items-center gap-2">
                <flux:button
                    variant="primary"
                    icon="plus"
                    wire:click="generateNew"
                >
                    Generate New
                </flux:button>
                <flux:button variant="ghost" icon="cog-6-tooth" href="{{ route('settings.linkedin') }}" />
            </div>
        </div>
    </div>

    {{-- Error Message --}}
    @if($error)
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div class="flex items-center gap-2 text-red-800 dark:text-red-300">
                <flux:icon name="exclamation-circle" class="size-5" />
                <span>{{ $error }}</span>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div>
            <flux:select wire:model.live="typeFilter" placeholder="All Types">
                <flux:select.option value="">All Types</flux:select.option>
                @foreach($postTypes as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="used">Used</flux:select.option>
                <flux:select.option value="dismissed">Dismissed</flux:select.option>
                <flux:select.option value="all">All</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Posts Grid --}}
    @if($posts->count() > 0)
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                <article
                    wire:key="post-{{ $post->id }}"
                    class="group rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden hover:shadow-md transition-shadow flex flex-col"
                >
                    {{-- Post Header --}}
                    <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" :color="$post->post_type->color()">
                                {{ $post->post_type->label() }}
                            </flux:badge>
                            @if($post->is_used)
                                <flux:badge size="sm" color="green">Used</flux:badge>
                            @endif
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $post->generated_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Post Content --}}
                    <div class="p-4 flex-1">
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">
                            {{ $post->title }}
                        </h3>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line line-clamp-6">
                            {{ $post->content }}
                        </div>

                        {{-- Hashtags --}}
                        @if($post->hashtags && count($post->hashtags) > 0)
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach($post->hashtags as $tag)
                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                        #{{ ltrim($tag, '#') }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- News Source --}}
                        @if($post->newsItem)
                            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-500">
                                <a
                                    href="{{ $post->newsItem->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-1"
                                >
                                    <flux:icon name="link" class="size-3" />
                                    Based on: {{ $post->newsItem->source }}
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="copyToClipboard({{ $post->id }})"
                                title="Copy to clipboard"
                            >
                                <flux:icon name="clipboard-document" class="size-4" />
                            </flux:button>
                            @if(!$post->is_used)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="markAsUsed({{ $post->id }})"
                                    title="Mark as used"
                                >
                                    <flux:icon name="check" class="size-4" />
                                </flux:button>
                            @endif
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="regenerate({{ $post->id }})"
                                wire:loading.attr="disabled"
                                wire:target="regenerate({{ $post->id }})"
                                title="Regenerate"
                            >
                                <flux:icon name="arrow-path" class="size-4" wire:loading.class="animate-spin" wire:target="regenerate({{ $post->id }})" />
                            </flux:button>
                        </div>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="dismiss({{ $post->id }})"
                            title="Dismiss"
                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            <flux:icon name="x-mark" class="size-4" />
                        </flux:button>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="py-16 text-center">
            <flux:icon name="pencil-square" class="size-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" />
            <h3 class="text-xl font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                No posts yet
            </h3>
            <p class="text-zinc-500 dark:text-zinc-400 mb-6 max-w-md mx-auto">
                Generate your first batch of LinkedIn posts using AI. Posts are tailored to your business and industry.
            </p>
            <flux:button
                variant="primary"
                icon="plus"
                wire:click="generateNew"
            >
                Generate Posts
            </flux:button>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('copy-to-clipboard', (event) => {
        navigator.clipboard.writeText(event.content).then(() => {
            // Could add a toast notification here
        });
    });
</script>
@endscript
