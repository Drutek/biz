<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Business Profile')" :subheading="__('Describe your business to help the AI advisor provide more relevant advice')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <h3 class="mb-3 font-medium text-zinc-900 dark:text-white">Financial Information</h3>
                <div class="space-y-4">
                    <flux:select
                        wire:model="currency"
                        :label="__('Currency')"
                        description="Currency used for all financial displays"
                    >
                        @foreach($this->currencyOptions as $code => $config)
                            <flux:select.option value="{{ $code }}">{{ $config['symbol'] }} - {{ $config['name'] }} ({{ $code }})</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input
                        wire:model="cash_balance"
                        :label="__('Current Cash Balance')"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Enter your current bank balance"
                        description="Used to calculate your runway (months until funds run out)"
                    />

                    <flux:input
                        wire:model="hourly_rate"
                        :label="__('Consulting Hourly Rate')"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="e.g., 150"
                        description="Your typical consulting rate - used to compare product ROI against consulting income"
                    />
                </div>
            </div>

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
