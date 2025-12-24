<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('API Keys & Settings')" :subheading="__('Configure your API keys and application settings')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <div class="space-y-4">
                <flux:heading size="sm">Company Settings</flux:heading>

                <flux:input
                    wire:model="company_name"
                    :label="__('Company Name')"
                    type="text"
                    placeholder="Your consulting firm name"
                />
                <flux:description>This will be used in the advisor context.</flux:description>
            </div>

            <flux:separator />

            <div class="space-y-4">
                <flux:heading size="sm">LLM Provider</flux:heading>

                <flux:radio.group wire:model="preferred_llm_provider" :label="__('Preferred AI Provider')">
                    <flux:radio value="claude" label="Claude (Anthropic)" description="Claude Sonnet 4 - Best for nuanced business advice" />
                    <flux:radio value="openai" label="OpenAI" description="GPT-4o - Fast and reliable" />
                </flux:radio.group>
                <flux:error name="preferred_llm_provider" />
            </div>

            <flux:separator />

            <div class="space-y-4">
                <flux:heading size="sm">API Keys</flux:heading>

                <flux:callout variant="warning">
                    API keys are stored securely. Leave blank to use keys from environment variables.
                </flux:callout>

                <flux:input
                    wire:model="anthropic_api_key"
                    :label="__('Anthropic API Key')"
                    type="password"
                    placeholder="sk-ant-..."
                    autocomplete="off"
                />

                <flux:input
                    wire:model="openai_api_key"
                    :label="__('OpenAI API Key')"
                    type="password"
                    placeholder="sk-..."
                    autocomplete="off"
                />

                <flux:input
                    wire:model="serpapi_key"
                    :label="__('SerpAPI Key')"
                    type="password"
                    placeholder="Your SerpAPI key for news fetching"
                    autocomplete="off"
                />
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save Settings') }}</flux:button>

                <x-action-message class="me-3" on="settings-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
