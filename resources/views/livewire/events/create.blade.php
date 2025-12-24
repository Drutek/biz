<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Log Business Event</flux:heading>
        <a href="{{ route('events.index') }}" wire:navigate>
            <flux:button variant="ghost" size="sm">
                <flux:icon.arrow-left class="mr-1 h-4 w-4" />
                Back to Events
            </flux:button>
        </a>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-6">
            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="title" placeholder="What happened?" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="4" placeholder="Provide more details about this event..." />
                <flux:error name="description" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Category</flux:label>
                    <flux:select wire:model="category">
                        @foreach($categories as $cat)
                            <flux:select.option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category" />
                </flux:field>

                <flux:field>
                    <flux:label>Significance</flux:label>
                    <flux:select wire:model="significance">
                        @foreach($significances as $sig)
                            <flux:select.option value="{{ $sig->value }}">{{ ucfirst($sig->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="significance" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>When did this occur?</flux:label>
                <flux:input type="datetime-local" wire:model="occurred_at" />
                <flux:error name="occurred_at" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <a href="{{ route('events.index') }}" wire:navigate>
                    <flux:button variant="ghost">Cancel</flux:button>
                </a>
                <flux:button type="submit" variant="primary">
                    Log Event
                </flux:button>
            </div>
        </form>
    </div>
</div>
