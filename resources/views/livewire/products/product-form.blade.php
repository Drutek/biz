<form wire:submit="save" class="space-y-4">
    <flux:field>
        <flux:label>Name</flux:label>
        <flux:input wire:model="name" placeholder="Product name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <flux:label>Description</flux:label>
        <flux:textarea wire:model="description" placeholder="Brief description of your product" rows="2" />
    </flux:field>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Product Type</flux:label>
            <flux:select wire:model="product_type">
                <option value="">Select type</option>
                @foreach($productTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="product_type" />
        </flux:field>

        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model="status">
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="status" />
        </flux:field>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <flux:field>
            <flux:label>Price</flux:label>
            <flux:input type="number" step="0.01" wire:model="price" placeholder="0.00" />
            <flux:error name="price" />
        </flux:field>

        <flux:field>
            <flux:label>Pricing Model</flux:label>
            <flux:select wire:model="pricing_model">
                @foreach($pricingModels as $model)
                    <option value="{{ $model->value }}">{{ $model->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="pricing_model" />
        </flux:field>

        <flux:field>
            <flux:label>Billing Frequency</flux:label>
            <flux:select wire:model="billing_frequency">
                <option value="">N/A (One-time)</option>
                @foreach($billingFrequencies as $frequency)
                    <option value="{{ $frequency->value }}">{{ $frequency->label() }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <flux:heading size="sm" class="mb-3">Revenue Metrics</flux:heading>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Monthly Recurring Revenue (MRR)</flux:label>
                <flux:input type="number" step="0.01" wire:model="mrr" placeholder="0.00" />
                <flux:error name="mrr" />
            </flux:field>

            <flux:field>
                <flux:label>Total Revenue (Lifetime)</flux:label>
                <flux:input type="number" step="0.01" wire:model="total_revenue" placeholder="0.00" />
                <flux:error name="total_revenue" />
            </flux:field>

            <flux:field>
                <flux:label>Subscriber Count</flux:label>
                <flux:input type="number" wire:model="subscriber_count" placeholder="0" />
                <flux:error name="subscriber_count" />
            </flux:field>

            <flux:field>
                <flux:label>Units Sold</flux:label>
                <flux:input type="number" wire:model="units_sold" placeholder="0" />
                <flux:error name="units_sold" />
            </flux:field>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <flux:heading size="sm" class="mb-3">Time Investment</flux:heading>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Hours Invested (Development)</flux:label>
                <flux:input type="number" step="0.5" wire:model="hours_invested" placeholder="0" />
                <flux:error name="hours_invested" />
            </flux:field>

            <flux:field>
                <flux:label>Monthly Maintenance Hours</flux:label>
                <flux:input type="number" step="0.5" wire:model="monthly_maintenance_hours" placeholder="0" />
                <flux:error name="monthly_maintenance_hours" />
            </flux:field>
        </div>
    </div>

    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <flux:heading size="sm" class="mb-3">Timeline</flux:heading>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Launch Date</flux:label>
                <flux:input type="date" wire:model="launched_at" />
            </flux:field>

            <flux:field>
                <flux:label>Target Launch Date</flux:label>
                <flux:input type="date" wire:model="target_launch_date" />
            </flux:field>
        </div>
    </div>

    <flux:field>
        <flux:label>Product URL</flux:label>
        <flux:input type="url" wire:model="url" placeholder="https://..." />
        <flux:error name="url" />
    </flux:field>

    <flux:field>
        <flux:label>Notes</flux:label>
        <flux:textarea wire:model="notes" placeholder="Additional notes" rows="2" />
    </flux:field>

    <div class="flex justify-end gap-2 pt-4">
        <flux:button variant="ghost" type="button" @click="$dispatch('close-modal')">Cancel</flux:button>
        <flux:button variant="primary" type="submit">
            {{ $product ? 'Update' : 'Create' }} Product
        </flux:button>
    </div>
</form>
