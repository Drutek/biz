<div>
    {{-- Masthead --}}
    <div class="mb-8 border-b-4 border-double border-zinc-800 dark:border-zinc-200 pb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-serif text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    The Business Advisor
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($edition)
                        {{ $edition->edition_date->format('l, F j, Y') }}
                    @else
                        {{ now()->format('l, F j, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <flux:button
                    variant="ghost"
                    wire:click="regenerate"
                    wire:loading.attr="disabled"
                    wire:target="regenerate"
                >
                    <span wire:loading.remove wire:target="regenerate">
                        <flux:icon name="arrow-path" class="size-4 mr-1" />
                        Regenerate
                    </span>
                    <span wire:loading wire:target="regenerate">
                        <flux:icon name="arrow-path" class="size-4 mr-1 animate-spin" />
                        Generating...
                    </span>
                </flux:button>
            </div>
        </div>
    </div>

    @if($edition)
        {{-- Headlines --}}
        <div class="mb-8">
            <h2 class="font-serif text-3xl font-bold leading-tight text-zinc-900 dark:text-zinc-100 mb-4">
                {{ $edition->headline }}
            </h2>
            <p class="text-lg text-zinc-700 dark:text-zinc-300 leading-relaxed border-l-4 border-zinc-300 dark:border-zinc-600 pl-4">
                {{ $edition->summary }}
            </p>
        </div>

        {{-- Sections --}}
        <div class="space-y-10">
            @foreach($edition->sections as $section)
                <section>
                    <div class="flex items-center gap-2 mb-4 border-b border-zinc-200 dark:border-zinc-700 pb-2">
                        @php
                            $icon = $section['icon'] ?? 'newspaper';
                        @endphp
                        <flux:icon :name="$icon" class="size-5 text-zinc-600 dark:text-zinc-400" />
                        <h3 class="font-serif text-xl font-bold text-zinc-800 dark:text-zinc-200">
                            {{ $section['title'] }}
                        </h3>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($section['articles'] ?? [] as $article)
                            @php
                                $newsItem = \App\Models\NewsItem::find($article['news_item_id'] ?? 0);
                            @endphp
                            <article
                                wire:key="article-{{ $article['news_item_id'] ?? uniqid() }}"
                                class="group rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden hover:shadow-md transition-shadow"
                            >
                                {{-- Thumbnail --}}
                                @if($newsItem?->thumbnail)
                                    <div class="aspect-video bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                                        <img
                                            src="{{ $newsItem->thumbnail }}"
                                            alt=""
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                        >
                                    </div>
                                @else
                                    <div class="aspect-video bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800 flex items-center justify-center">
                                        <flux:icon name="newspaper" class="size-12 text-zinc-300 dark:text-zinc-600" />
                                    </div>
                                @endif

                                <div class="p-4">
                                    {{-- Headline --}}
                                    <h4 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2 line-clamp-2">
                                        @if($newsItem)
                                            <a
                                                href="{{ $newsItem->url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="hover:text-blue-600 dark:hover:text-blue-400"
                                                wire:click="markArticleRead({{ $newsItem->id }})"
                                            >
                                                {{ $article['headline'] ?? $newsItem->title }}
                                            </a>
                                        @else
                                            {{ $article['headline'] ?? 'Article' }}
                                        @endif
                                    </h4>

                                    {{-- Summary --}}
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3 line-clamp-3">
                                        {{ $article['summary'] ?? '' }}
                                    </p>

                                    {{-- Insight --}}
                                    @if(!empty($article['insight']))
                                        <div class="text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 rounded px-3 py-2 mb-3">
                                            <span class="font-medium">Why it matters:</span> {{ $article['insight'] }}
                                        </div>
                                    @endif

                                    {{-- Meta --}}
                                    @if($newsItem)
                                        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-500">
                                            <span>{{ $newsItem->source }}</span>
                                            <span>{{ $newsItem->fetched_at->diffForHumans() }}</span>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="mt-10 pt-4 border-t border-zinc-200 dark:border-zinc-700 text-center text-sm text-zinc-500 dark:text-zinc-400">
            Generated {{ $edition->generated_at->diffForHumans() }}
        </div>
    @else
        {{-- Error Message --}}
        @if($error)
            <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <div class="flex items-center gap-2 text-red-800 dark:text-red-300">
                    <flux:icon name="exclamation-circle" class="size-5" />
                    <span>{{ $error }}</span>
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        <div class="py-16 text-center">
            <flux:icon name="newspaper" class="size-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" />
            <h3 class="text-xl font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                No edition available yet
            </h3>

            @if(!$hasTrackedEntities)
                <p class="text-zinc-500 dark:text-zinc-400 mb-6 max-w-md mx-auto">
                    Add some companies or competitors to track first, then news will be fetched automatically.
                </p>
                <flux:button variant="primary" href="{{ route('tracked-entities.index') }}">
                    Add Tracked Entities
                </flux:button>
            @elseif(!$hasRecentNews)
                <p class="text-zinc-500 dark:text-zinc-400 mb-6 max-w-md mx-auto">
                    No recent news available. News is fetched every 4 hours. You can trigger a fetch manually.
                </p>
                <flux:button variant="primary" wire:click="$dispatch('fetch-news')">
                    Check for News
                </flux:button>
            @else
                <p class="text-zinc-500 dark:text-zinc-400 mb-6 max-w-md mx-auto">
                    Your personalized newspaper will be generated automatically each morning, or you can generate one now.
                </p>
                <flux:button
                    variant="primary"
                    wire:click="regenerate"
                    wire:loading.attr="disabled"
                    wire:target="regenerate"
                >
                    <span wire:loading.remove wire:target="regenerate">Generate Today's Edition</span>
                    <span wire:loading wire:target="regenerate">
                        <flux:icon name="arrow-path" class="size-4 mr-1 animate-spin inline" />
                        Generating (this may take a minute)...
                    </span>
                </flux:button>
            @endif
        </div>
    @endif
</div>
