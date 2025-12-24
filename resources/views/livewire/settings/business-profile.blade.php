<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Business Profile')" :subheading="__('Describe your business to help the AI advisor provide more relevant advice')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <flux:input
                wire:model="business_industry"
                :label="__('Industry / Sector')"
                type="text"
                placeholder="e.g., Software Development, Marketing, Financial Services"
            />

            <flux:textarea
                wire:model="business_description"
                :label="__('Business Description')"
                placeholder="Describe what your business does, your unique value proposition, and your business model..."
                rows="4"
            />

            <flux:textarea
                wire:model="business_target_market"
                :label="__('Target Market')"
                placeholder="Describe your ideal clients, their size, industry, and typical challenges..."
                rows="3"
            />

            <flux:textarea
                wire:model="business_key_services"
                :label="__('Key Services / Products')"
                placeholder="List your main offerings, e.g., consulting packages, software products, retainer services..."
                rows="3"
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save Profile') }}</flux:button>

                <x-action-message class="me-3" on="settings-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
