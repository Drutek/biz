<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Briefing Archive</flux:heading>
        <a href="{{ route('standup.today') }}" wire:navigate>
            <flux:button variant="ghost" size="sm">
                <flux:icon.calendar-days class="mr-1 h-4 w-4" />
                Today's Briefing
            </flux:button>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- List of past standups --}}
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-white">Past Briefings</h3>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($this->standups as $standup)
                    <button
                        wire:click="selectDate('{{ $standup->standup_date->format('Y-m-d') }}')"
                        class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/50 {{ $selectedDate === $standup->standup_date->format('Y-m-d') ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                    >
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ $standup->standup_date->format('l, M j') }}
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $standup->standup_date->format('Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($standup->hasAlerts())
                                <flux:badge size="sm" color="amber">Alerts</flux:badge>
                            @endif
                            @if($standup->viewed_at)
                                <flux:icon.check class="h-4 w-4 text-green-500" />
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                        No past briefings available
                    </div>
                @endforelse
            </div>
            @if($this->standups->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->standups->links() }}
                </div>
            @endif
        </div>

        {{-- Selected standup details --}}
        <div class="lg:col-span-2">
            @if($selectedStandup)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $selectedStandup->standup_date->format('l, F j, Y') }}
                        </h3>
                        <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                            <flux:icon.x-mark class="h-4 w-4" />
                        </flux:button>
                    </div>

                    {{-- Financial Snapshot --}}
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <h4 class="mb-3 font-medium text-zinc-900 dark:text-white">Financial Snapshot</h4>
                        @if($selectedStandup->financial_snapshot)
                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Cash Balance</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">
                                        ${{ number_format($selectedStandup->financial_snapshot['cash_balance'] ?? 0, 0) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Runway</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white">
                                        @if(($selectedStandup->financial_snapshot['runway_months'] ?? 0) > 100)
                                            Infinite
                                        @else
                                            {{ number_format($selectedStandup->financial_snapshot['runway_months'] ?? 0, 1) }} months
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Monthly Income</dt>
                                    <dd class="font-medium text-green-600 dark:text-green-400">
                                        +${{ number_format($selectedStandup->financial_snapshot['monthly_income'] ?? 0, 0) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Monthly Expenses</dt>
                                    <dd class="font-medium text-red-600 dark:text-red-400">
                                        -${{ number_format($selectedStandup->financial_snapshot['monthly_expenses'] ?? 0, 0) }}
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No data available</p>
                        @endif
                    </div>

                    {{-- Alerts --}}
                    @if($selectedStandup->alerts && count($selectedStandup->alerts) > 0)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <h4 class="mb-3 font-medium text-zinc-900 dark:text-white">Alerts</h4>
                            <div class="space-y-2">
                                @foreach($selectedStandup->alerts as $key => $alert)
                                    <div class="flex items-start gap-2 text-sm">
                                        <flux:icon.exclamation-circle class="mt-0.5 h-4 w-4 text-amber-500" />
                                        <span class="text-zinc-700 dark:text-zinc-300">
                                            @if(is_array($alert))
                                                {{ $alert['message'] ?? json_encode($alert) }}
                                            @else
                                                {{ $alert }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- AI Summary --}}
                    @if($selectedStandup->ai_summary)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <h4 class="mb-3 font-medium text-zinc-900 dark:text-white">AI Summary</h4>
                            <div class="prose prose-sm prose-zinc max-w-none dark:prose-invert">
                                {!! Str::markdown($selectedStandup->ai_summary) !!}
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex h-64 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50">
                    <div class="text-center">
                        <flux:icon.document-text class="mx-auto h-10 w-10 text-zinc-400 dark:text-zinc-500" />
                        <p class="mt-2 text-zinc-500 dark:text-zinc-400">
                            Select a briefing to view details
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
