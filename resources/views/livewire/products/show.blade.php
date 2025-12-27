<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('products.index')">
                <flux:icon name="arrow-left" class="size-4" />
            </flux:button>
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl">{{ $product->name }}</flux:heading>
                    <flux:badge :color="$product->status->color()">{{ $product->status->label() }}</flux:badge>
                </div>
                @if($product->description)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $product->description }}</p>
                @endif
            </div>
        </div>
        @if($product->url)
            <flux:button variant="ghost" :href="$product->url" target="_blank">
                <flux:icon name="arrow-top-right-on-square" class="mr-1 size-4" />
                Visit
            </flux:button>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Revenue Overview --}}
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-4">Revenue Overview</flux:heading>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @if($product->pricing_model->hasRecurringRevenue())
                        <div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($product->mrr, 0) }}</div>
                            <div class="text-sm text-zinc-500">Monthly Recurring</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $product->subscriber_count }}</div>
                            <div class="text-sm text-zinc-500">Subscribers</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($product->total_revenue, 0) }}</div>
                        <div class="text-sm text-zinc-500">Total Revenue</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $product->units_sold }}</div>
                        <div class="text-sm text-zinc-500">Units Sold</div>
                    </div>
                </div>

                @if($revenueSnapshots->count() > 1)
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-2">Revenue Trend (Last 6 months)</flux:heading>
                        <div class="flex items-center gap-2">
                            @php
                                $trend = $product->revenueTrend();
                            @endphp
                            @if($trend > 0)
                                <flux:icon name="arrow-trending-up" class="size-5 text-green-500" />
                                <span class="text-green-600">+{{ number_format($trend, 1) }}%</span>
                            @elseif($trend < 0)
                                <flux:icon name="arrow-trending-down" class="size-5 text-red-500" />
                                <span class="text-red-600">{{ number_format($trend, 1) }}%</span>
                            @else
                                <span class="text-zinc-500">No change</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Milestones --}}
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">Milestones</flux:heading>
                    <flux:button size="sm" wire:click="createMilestone">Add Milestone</flux:button>
                </div>

                @if($product->milestones->count() > 0)
                    <div class="space-y-3">
                        @foreach($product->milestones as $milestone)
                            <div wire:key="milestone-{{ $milestone->id }}" class="flex items-start gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                <div class="mt-0.5">
                                    @if($milestone->status === \App\Enums\MilestoneStatus::Completed)
                                        <flux:icon name="check-circle" class="size-5 text-green-500" />
                                    @elseif($milestone->status === \App\Enums\MilestoneStatus::Blocked)
                                        <flux:icon name="x-circle" class="size-5 text-red-500" />
                                    @elseif($milestone->status === \App\Enums\MilestoneStatus::InProgress)
                                        <flux:icon name="arrow-path" class="size-5 text-blue-500" />
                                    @else
                                        <flux:icon name="clock" class="size-5 text-zinc-400" />
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $milestone->title }}</span>
                                        <flux:badge size="sm" :color="$milestone->status->color()">{{ $milestone->status->label() }}</flux:badge>
                                        @if($milestone->isOverdue())
                                            <flux:badge size="sm" color="red">Overdue</flux:badge>
                                        @endif
                                    </div>
                                    @if($milestone->description)
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $milestone->description }}</p>
                                    @endif
                                    @if($milestone->target_date)
                                        <div class="mt-1 text-xs text-zinc-400">
                                            Target: {{ $milestone->target_date->format('M j, Y') }}
                                            @if($milestone->completed_at)
                                                | Completed: {{ $milestone->completed_at->format('M j, Y') }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex gap-1">
                                    @if($milestone->status !== \App\Enums\MilestoneStatus::Completed)
                                        <flux:button size="sm" variant="ghost" wire:click="completeMilestone({{ $milestone->id }})" title="Mark complete">
                                            <flux:icon name="check" class="size-4" />
                                        </flux:button>
                                    @endif
                                    <flux:button size="sm" variant="ghost" wire:click="editMilestone({{ $milestone->id }})">Edit</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="Delete this milestone?">Delete</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">No milestones yet. Add one to track development progress.</p>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Product Details --}}
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-4">Details</flux:heading>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Type</dt>
                        <dd class="flex items-center gap-2">
                            <flux:icon :name="$product->product_type->icon()" class="size-4 text-zinc-400" />
                            {{ $product->product_type->label() }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Pricing</dt>
                        <dd>
                            @if($product->price)
                                ${{ number_format($product->price, 2) }}
                                ({{ $product->pricing_model->label() }})
                            @else
                                {{ $product->pricing_model->label() }}
                            @endif
                        </dd>
                    </div>
                    @if($product->launched_at)
                        <div>
                            <dt class="text-sm text-zinc-500">Launched</dt>
                            <dd>{{ $product->launched_at->format('M j, Y') }}</dd>
                        </div>
                    @elseif($product->target_launch_date)
                        <div>
                            <dt class="text-sm text-zinc-500">Target Launch</dt>
                            <dd>
                                {{ $product->target_launch_date->format('M j, Y') }}
                                @if($product->daysToLaunch() !== null)
                                    <span class="text-sm text-zinc-400">({{ $product->daysToLaunch() }} days)</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Time Investment --}}
            <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-4">Time Investment</flux:heading>
                @php
                    $hourlyRate = (float) \App\Models\Setting::get(\App\Models\Setting::KEY_HOURLY_RATE, 0);
                    $timeInvested = $product->hours_invested * $hourlyRate;
                    $profit = $product->total_revenue - $timeInvested;
                @endphp
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Hours Invested</dt>
                        <dd class="text-xl font-bold">{{ number_format($product->hours_invested, 1) }}h</dd>
                    </div>
                    @if($hourlyRate > 0)
                        <div>
                            <dt class="text-sm text-zinc-500">Opportunity Cost</dt>
                            <dd class="text-xl font-bold">{{ \App\Models\Setting::formatCurrency($timeInvested) }}</dd>
                            <dd class="text-xs text-zinc-400">@ {{ \App\Models\Setting::formatCurrency($hourlyRate) }}/hr</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm text-zinc-500">Monthly Maintenance</dt>
                        <dd class="text-xl font-bold">{{ number_format($product->monthly_maintenance_hours, 1) }}h/mo</dd>
                    </div>
                    @if($hourlyRate > 0)
                        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <dt class="text-sm text-zinc-500">Profit / Loss</dt>
                            <dd class="text-xl font-bold {{ $profit >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $profit >= 0 ? '+' : '' }}{{ \App\Models\Setting::formatCurrency($profit) }}
                            </dd>
                        </div>
                    @endif
                    @if($product->hours_invested > 0 && $product->total_revenue > 0)
                        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <dt class="text-sm text-zinc-500">Effective Hourly Rate</dt>
                            <dd class="text-xl font-bold {{ $product->effectiveHourlyRate() >= $hourlyRate ? 'text-green-600' : 'text-amber-500' }}">
                                {{ \App\Models\Setting::formatCurrency($product->effectiveHourlyRate()) }}/hr
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Notes --}}
            @if($product->notes)
                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
                    <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                    <p class="whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-400">{{ $product->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <flux:modal name="milestone-form" wire:model.self="showMilestoneForm" @close="closeMilestoneForm">
        <div class="p-6">
            <flux:heading size="lg" class="mb-4">
                {{ $editingMilestone ? 'Edit Milestone' : 'Add Milestone' }}
            </flux:heading>
            <livewire:products.milestone-form
                :product="$product"
                :milestone="$editingMilestone"
                :key="$editingMilestone?->id ?? 'new-milestone'"
            />
        </div>
    </flux:modal>
</div>
