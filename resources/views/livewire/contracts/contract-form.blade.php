<form wire:submit="save" class="space-y-4">
    <flux:field>
        <flux:label>Name</flux:label>
        <flux:input wire:model="name" placeholder="Client name or project" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <flux:label>Description</flux:label>
        <flux:textarea wire:model="description" placeholder="Optional description" rows="2" />
    </flux:field>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Value</flux:label>
            <flux:input type="number" step="0.01" wire:model="value" placeholder="0.00" />
            <flux:error name="value" />
        </flux:field>

        <flux:field>
            <flux:label>Billing Frequency</flux:label>
            <flux:select wire:model="billing_frequency">
                <option value="">Select frequency</option>
                @foreach($billingFrequencies as $frequency)
                    <option value="{{ $frequency->value }}">{{ $frequency->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="billing_frequency" />
        </flux:field>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Start Date</flux:label>
            <flux:input type="date" wire:model="start_date" />
            <flux:error name="start_date" />
        </flux:field>

        <flux:field>
            <flux:label>End Date (Optional)</flux:label>
            <flux:input type="date" wire:model="end_date" />
        </flux:field>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model="status">
                <option value="">Select status</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="status" />
        </flux:field>

        <flux:field>
            <flux:label>Probability (%)</flux:label>
            <flux:input type="number" min="0" max="100" wire:model="probability" />
            <flux:error name="probability" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label>Notes</flux:label>
        <flux:textarea wire:model="notes" placeholder="Optional notes" rows="2" />
    </flux:field>

    <div class="flex justify-end gap-2 pt-4">
        <flux:button variant="ghost" type="button" @click="$dispatch('close-modal')">Cancel</flux:button>
        <flux:button variant="primary" type="submit">
            {{ $contract ? 'Update' : 'Create' }} Contract
        </flux:button>
    </div>
</form>
