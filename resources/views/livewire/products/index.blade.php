<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" wire:click="create">
            Add Product
        </flux:button>
    </div>

    <div class="mb-4 flex gap-4">
        <flux:select wire:model.live="filterStatus" class="w-48">
            <option value="">All Statuses</option>
            @foreach($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterType" class="w-48">
            <option value="">All Types</option>
            @foreach($types as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Time</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-2">
                                <flux:icon :name="$product->product_type->icon()" class="size-5 text-zinc-400" />
                                <div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        <a href="{{ route('products.show', $product) }}" class="hover:underline">
                                            {{ $product->name }}
                                        </a>
                                    </div>
                                    @if($product->description)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ Str::limit($product->description, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="$product->product_type->color()">
                                {{ $product->product_type->label() }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <flux:badge :color="$product->status->color()">
                                {{ $product->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                            @if($product->pricing_model->hasRecurringRevenue())
                                <div>${{ number_format($product->mrr, 0) }}/mo</div>
                                <div class="text-xs text-zinc-500">{{ $product->subscriber_count }} subs</div>
                            @else
                                <div>${{ number_format($product->total_revenue, 0) }}</div>
                                <div class="text-xs text-zinc-500">{{ $product->units_sold }} sold</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            @php
                                $hourlyRate = (float) \App\Models\Setting::get(\App\Models\Setting::KEY_HOURLY_RATE, 0);
                                $timeInvested = $product->hours_invested * $hourlyRate;
                                $profit = $product->total_revenue - $timeInvested;
                            @endphp
                            <div>{{ number_format($product->hours_invested, 0) }}h ({{ \App\Models\Setting::formatCurrency($timeInvested, null, 0) }})</div>
                            @if($product->total_revenue > 0 || $timeInvested > 0)
                                <div class="text-xs {{ $profit >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $profit >= 0 ? '+' : '' }}{{ \App\Models\Setting::formatCurrency($profit, null, 0) }}
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button size="sm" variant="ghost" :href="route('products.show', $product)">View</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $product->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $product->id }})" wire:confirm="Are you sure you want to delete this product?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No products yet. Click "Add Product" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="product-form" wire:model.self="showForm" @close="closeForm">
        <div class="max-h-[80vh] overflow-y-auto p-6">
            <flux:heading size="lg" class="mb-4">
                {{ $editingProduct ? 'Edit Product' : 'Add Product' }}
            </flux:heading>
            <livewire:products.product-form :product="$editingProduct" :key="$editingProduct?->id ?? 'new'" />
        </div>
    </flux:modal>
</div>
