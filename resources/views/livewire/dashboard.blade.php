<div>
    <div class="mb-6">
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:text class="text-zinc-500">Your business at a glance</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Monthly Income</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ number_format($summary['monthly_income'], 2) }}
            </div>
            <flux:text class="text-sm text-zinc-500">Confirmed contracts</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Monthly Expenses</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ number_format($summary['monthly_expenses'], 2) }}
            </div>
            <flux:text class="text-sm text-zinc-500">Recurring costs</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Monthly Net</flux:text>
            <div class="mt-1 text-2xl font-bold {{ $summary['monthly_net'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $summary['monthly_net'] >= 0 ? '+' : '' }}{{ number_format($summary['monthly_net'], 2) }}
            </div>
            <flux:text class="text-sm text-zinc-500">Income - Expenses</flux:text>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500">Runway</flux:text>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                @if($summary['runway_months'] === INF)
                    Sustainable
                @else
                    {{ number_format($summary['runway_months'], 1) }} months
                @endif
            </div>
            <flux:text class="text-sm text-zinc-500">
                @if($summary['monthly_pipeline'] > 0)
                    + {{ number_format($summary['monthly_pipeline'], 2) }} pipeline
                @else
                    At current rate
                @endif
            </flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">12-Month Cashflow Projection</flux:heading>
            <div class="h-64" id="cashflow-chart">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Upcoming Contract Ends</flux:heading>
            @if($upcomingEndDates->count() > 0)
                <div class="space-y-3">
                    @foreach($upcomingEndDates as $contract)
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $contract->name }}</div>
                                <div class="text-xs text-zinc-500">{{ number_format($contract->monthlyValue(), 2) }}/mo</div>
                            </div>
                            <flux:badge :color="$contract->end_date->diffInDays(now()) <= 30 ? 'red' : 'yellow'">
                                {{ $contract->end_date->format('M j') }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500">No contracts ending in the next 3 months.</flux:text>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Recent News</flux:heading>
                @if(Route::has('news.index'))
                    <flux:button size="sm" variant="ghost" href="{{ route('news.index') }}" wire:navigate>View all</flux:button>
                @endif
            </div>
            @if($recentNews->count() > 0)
                <div class="space-y-3">
                    @foreach($recentNews as $news)
                        <div class="border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            <a href="{{ $news->url }}" target="_blank" class="text-sm font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400">
                                {{ $news->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1">
                                <flux:badge size="sm">{{ $news->trackedEntity->name }}</flux:badge>
                                <flux:text class="text-xs text-zinc-500">{{ $news->source }} - {{ $news->fetched_at->diffForHumans() }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500">No recent news. Add tracked entities to start monitoring.</flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">Quick Advisor</flux:heading>
            @if(Route::has('advisor.index'))
                <flux:text class="text-zinc-500 mb-4">Get AI-powered strategic advice about your business, cashflow, and market opportunities.</flux:text>
                <flux:button variant="primary" class="w-full" href="{{ route('advisor.index') }}" wire:navigate>Open Advisor</flux:button>
            @else
                <flux:text class="text-zinc-500">Configure your API keys in Settings to enable the AI advisor.</flux:text>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('cashflowChart');
            if (ctx) {
                const projections = @json($projections);
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: projections.map(p => p.month),
                        datasets: [
                            {
                                label: 'Income',
                                data: projections.map(p => p.income),
                                backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1
                            },
                            {
                                label: 'Expenses',
                                data: projections.map(p => p.expenses),
                                backgroundColor: 'rgba(239, 68, 68, 0.5)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</div>
