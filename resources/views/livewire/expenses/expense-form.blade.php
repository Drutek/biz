<form wire:submit="save" class="space-y-4">
    <flux:field>
        <flux:label>Name</flux:label>
        <flux:input wire:model="name" placeholder="Expense name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <flux:label>Description</flux:label>
        <flux:textarea wire:model="description" placeholder="Optional description" rows="2" />
    </flux:field>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Amount</flux:label>
            <flux:input type="number" step="0.01" wire:model="amount" placeholder="0.00" />
            <flux:error name="amount" />
        </flux:field>

        <flux:field>
            <flux:label>Frequency</flux:label>
            <flux:select wire:model="frequency">
                <option value="">Select frequency</option>
                @foreach($frequencies as $freq)
                    <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="frequency" />
        </flux:field>
    </div>

    <flux:field>
        <flux:label>Category</flux:label>
        <flux:select wire:model="category">
            <option value="">Select category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
            @endforeach
        </flux:select>
        <flux:error name="category" />
    </flux:field>

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

    <flux:field>
        <flux:checkbox wire:model="is_active" label="Active" />
    </flux:field>

    <div class="flex justify-end gap-2 pt-4">
        <flux:button variant="ghost" type="button" @click="$dispatch('close-modal')">Cancel</flux:button>
        <flux:button variant="primary" type="submit">
            {{ $expense ? 'Update' : 'Create' }} Expense
        </flux:button>
    </div>
</form>
