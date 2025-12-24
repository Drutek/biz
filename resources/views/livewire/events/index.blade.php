<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Business Events</flux:heading>
        <a href="{{ route('events.create') }}" wire:navigate>
            <flux:button variant="primary" size="sm">
                <flux:icon.plus class="mr-1 h-4 w-4" />
                Log Event
            </flux:button>
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4">
        <div class="w-48">
            <flux:select wire:model.live="category" placeholder="All Categories">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach($categories as $cat)
                    <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="significance" placeholder="All Significance">
                <flux:select.option value="">All Significance</flux:select.option>
                @foreach($significances as $sig)
                    <flux:select.option value="{{ $sig->value }}">{{ ucfirst($sig->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @forelse($this->events as $event)
            <div
                wire:key="event-{{ $event->id }}"
                class="flex gap-4 border-b border-zinc-100 p-4 last:border-b-0 dark:border-zinc-700"
            >
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    @php
                        $iconClass = match($event->significance->value) {
                            'critical' => 'text-red-500',
                            'high' => 'text-amber-500',
                            'medium' => 'text-blue-500',
                            default => 'text-zinc-400',
                        };
                    @endphp
                    @switch($event->category->value)
                        @case('financial')
                            <flux:icon.banknotes class="h-6 w-6 {{ $iconClass }}" />
                            @break
                        @case('market')
                            <flux:icon.chart-bar class="h-6 w-6 {{ $iconClass }}" />
                            @break
                        @case('advisory')
                            <flux:icon.light-bulb class="h-6 w-6 {{ $iconClass }}" />
                            @break
                        @case('milestone')
                            <flux:icon.flag class="h-6 w-6 {{ $iconClass }}" />
                            @break
                        @default
                            <flux:icon.calendar class="h-6 w-6 {{ $iconClass }}" />
                    @endswitch
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">
                                {{ $event->title }}
                            </h4>
                            @if($event->description)
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ Str::limit($event->description, 200) }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-shrink-0 items-center gap-2">
                            <flux:badge size="sm" :color="match($event->significance->value) {
                                'critical' => 'red',
                                'high' => 'amber',
                                'medium' => 'blue',
                                default => 'zinc',
                            }">
                                {{ ucfirst($event->significance->value) }}
                            </flux:badge>
                            <flux:badge size="sm" color="zinc">
                                {{ ucfirst($event->category->value) }}
                            </flux:badge>
                        </div>
                    </div>

                    <div class="mt-2 flex items-center gap-4 text-xs text-zinc-500">
                        <span>{{ $event->occurred_at->format('M j, Y g:i A') }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                        <span>{{ $event->occurred_at->diffForHumans() }}</span>
                        @if($event->event_type)
                            <span class="text-zinc-300 dark:text-zinc-600">|</span>
                            <span>{{ str_replace('_', ' ', ucfirst($event->event_type->value)) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-4 py-12 text-center">
                <flux:icon.calendar class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <h3 class="mt-4 font-medium text-zinc-900 dark:text-white">No events yet</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Business events will appear here as they occur.
                </p>
                <div class="mt-4">
                    <a href="{{ route('events.create') }}" wire:navigate>
                        <flux:button variant="primary" size="sm">Log Your First Event</flux:button>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    @if($this->events->hasPages())
        <div class="mt-4">
            {{ $this->events->links() }}
        </div>
    @endif
</div>
