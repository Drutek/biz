<form wire:submit="save" class="space-y-4">
    <flux:field>
        <flux:label>Title</flux:label>
        <flux:input wire:model="title" placeholder="e.g., Complete first draft" />
        <flux:error name="title" />
    </flux:field>

    <flux:field>
        <flux:label>Description</flux:label>
        <flux:textarea wire:model="description" placeholder="Optional details" rows="2" />
    </flux:field>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model="status">
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </flux:select>
            <flux:error name="status" />
        </flux:field>

        <flux:field>
            <flux:label>Target Date</flux:label>
            <flux:input type="date" wire:model="target_date" />
        </flux:field>
    </div>

    <div class="flex justify-end gap-2 pt-4">
        <flux:button variant="ghost" type="button" @click="$dispatch('close-modal')">Cancel</flux:button>
        <flux:button variant="primary" type="submit">
            {{ $milestone ? 'Update' : 'Add' }} Milestone
        </flux:button>
    </div>
</form>
