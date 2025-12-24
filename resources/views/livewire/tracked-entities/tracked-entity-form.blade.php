<form wire:submit="save" class="space-y-4">
    <flux:field>
        <flux:label>Name</flux:label>
        <flux:input wire:model="name" placeholder="Company, topic, or industry name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <flux:label>Type</flux:label>
        <flux:select wire:model="entity_type">
            <option value="">Select type</option>
            @foreach($types as $typeOption)
                <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
            @endforeach
        </flux:select>
        <flux:error name="entity_type" />
    </flux:field>

    <flux:field>
        <flux:label>Search Query</flux:label>
        <flux:textarea wire:model="search_query" placeholder="Search terms for news (defaults to name + 'news')" rows="2" />
        <flux:description>The search query used when fetching news about this entity.</flux:description>
    </flux:field>

    <flux:field>
        <flux:checkbox wire:model="is_active" label="Active" />
        <flux:description>Only active entities are monitored for news.</flux:description>
    </flux:field>

    <div class="flex justify-end gap-2 pt-4">
        <flux:button variant="ghost" type="button" @click="$dispatch('close-modal')">Cancel</flux:button>
        <flux:button variant="primary" type="submit">
            {{ $entityId ? 'Update' : 'Create' }} Entity
        </flux:button>
    </div>
</form>
