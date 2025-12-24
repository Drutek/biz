<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-zinc-900 dark:text-white">Today's Briefing</h3>
        <a href="{{ route('standup.today') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
            View Full Briefing
        </a>
    </div>

    @if($standup)
        <div class="mt-4 space-y-4">
            {{-- Quick Stats --}}
            @if($standup->financial_snapshot)
                <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Cash</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            ${{ number_format($standup->financial_snapshot['cash_balance'] ?? 0, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Net/Month</p>
                        @php
                            $net = ($standup->financial_snapshot['monthly_income'] ?? 0) - ($standup->financial_snapshot['monthly_expenses'] ?? 0);
                        @endphp
                        <p class="font-medium {{ $net >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $net >= 0 ? '+' : '' }}${{ number_format($net, 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Runway</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            @if(($standup->financial_snapshot['runway_months'] ?? 0) > 100)
                                Infinite
                            @else
                                {{ number_format($standup->financial_snapshot['runway_months'] ?? 0, 1) }}mo
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Unread Insights</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            {{ $this->unreadInsightsCount }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Alerts Summary --}}
            @if($standup->hasAlerts())
                <div class="rounded-md bg-amber-50 p-3 dark:bg-amber-900/20">
                    <div class="flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                        <flux:icon.exclamation-triangle class="h-4 w-4" />
                        <span>{{ count($standup->alerts) }} alert{{ count($standup->alerts) > 1 ? 's' : '' }} require your attention</span>
                    </div>
                </div>
            @else
                <div class="rounded-md bg-green-50 p-3 dark:bg-green-900/20">
                    <div class="flex items-center gap-2 text-sm text-green-800 dark:text-green-200">
                        <flux:icon.check-circle class="h-4 w-4" />
                        <span>All systems normal</span>
                    </div>
                </div>
            @endif

            {{-- AI Summary Preview --}}
            @if($standup->ai_summary)
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ Str::limit($standup->ai_summary, 200) }}
                </div>
            @endif
        </div>
    @else
        <div class="mt-4 text-center text-zinc-500 dark:text-zinc-400">
            <p>No briefing available yet.</p>
        </div>
    @endif
</div>
