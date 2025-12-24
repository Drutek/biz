<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Today's Briefing</flux:heading>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ now()->format('l, F j, Y') }}
            </p>
        </div>
        <a href="{{ route('standup.archive') }}" wire:navigate>
            <flux:button variant="ghost" size="sm">
                <flux:icon.archive-box class="mr-1 h-4 w-4" />
                View Archive
            </flux:button>
        </a>
    </div>

    @if($standup)
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Financial Snapshot --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">Financial Snapshot</h3>
                @if($standup->financial_snapshot)
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Cash Balance</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                ${{ number_format($standup->financial_snapshot['cash_balance'] ?? 0, 0) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Monthly Income</dt>
                            <dd class="font-medium text-green-600 dark:text-green-400">
                                +${{ number_format($standup->financial_snapshot['monthly_income'] ?? 0, 0) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Monthly Expenses</dt>
                            <dd class="font-medium text-red-600 dark:text-red-400">
                                -${{ number_format($standup->financial_snapshot['monthly_expenses'] ?? 0, 0) }}
                            </dd>
                        </div>
                        <div class="flex justify-between border-t border-zinc-100 pt-3 dark:border-zinc-700">
                            <dt class="text-zinc-500 dark:text-zinc-400">Net Cashflow</dt>
                            @php
                                $netCashflow = ($standup->financial_snapshot['monthly_income'] ?? 0) - ($standup->financial_snapshot['monthly_expenses'] ?? 0);
                            @endphp
                            <dd class="font-medium {{ $netCashflow >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $netCashflow >= 0 ? '+' : '' }}${{ number_format($netCashflow, 0) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Runway</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                @if(($standup->financial_snapshot['runway_months'] ?? 0) > 100)
                                    Infinite
                                @else
                                    {{ number_format($standup->financial_snapshot['runway_months'] ?? 0, 1) }} months
                                @endif
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-zinc-500 dark:text-zinc-400">No financial data available.</p>
                @endif
            </div>

            {{-- Alerts --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">Alerts</h3>
                @if($standup->alerts && count($standup->alerts) > 0)
                    <div class="space-y-3">
                        @foreach($standup->alerts as $key => $alert)
                            <div class="flex items-start gap-3 rounded-md bg-zinc-50 p-3 dark:bg-zinc-700/50">
                                @if(str_starts_with($key, 'contract_'))
                                    <flux:icon.document-text class="mt-0.5 h-5 w-5 text-amber-500" />
                                @elseif($key === 'runway')
                                    <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 text-red-500" />
                                @elseif($key === 'urgent_events')
                                    <flux:icon.bolt class="mt-0.5 h-5 w-5 text-purple-500" />
                                @elseif($key === 'unread_insights')
                                    <flux:icon.light-bulb class="mt-0.5 h-5 w-5 text-blue-500" />
                                @else
                                    <flux:icon.bell class="mt-0.5 h-5 w-5 text-zinc-500" />
                                @endif
                                <div>
                                    @if(is_array($alert))
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ $alert['message'] ?? json_encode($alert) }}
                                        </p>
                                    @else
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $alert }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <flux:icon.check-circle class="h-5 w-5" />
                        <span>No alerts - all systems normal</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- AI Summary --}}
        @if($standup->ai_summary)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">AI Summary</h3>
                <div class="prose prose-sm max-w-none dark:prose-invert">
                    {!! nl2br(e($standup->ai_summary)) !!}
                </div>
            </div>
        @endif

        {{-- AI Insights --}}
        @if($standup->ai_insights && count($standup->ai_insights) > 0)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">Today's Insights</h3>
                <ul class="space-y-3">
                    @foreach($standup->ai_insights as $insight)
                        <li class="flex items-start gap-3">
                            <flux:icon.light-bulb class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $insight }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon.document-text class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
            <h3 class="mt-4 font-medium text-zinc-900 dark:text-white">No briefing available</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Your daily briefing will appear here once generated.
            </p>
        </div>
    @endif
</div>
