<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Task Integrations')" :subheading="__('Connect your external task manager to export tasks')">
        <div class="my-6 w-full space-y-6">
            {{-- Provider Selection --}}
            <div class="space-y-4">
                <flux:heading size="sm">Select a Provider</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($this->providers as $provider)
                        <button
                            type="button"
                            wire:click="selectProvider('{{ $provider['key'] }}')"
                            @class([
                                'relative rounded-lg border-2 p-4 text-left transition-all',
                                'border-blue-500 bg-blue-50 dark:bg-blue-900/20' => $selectedProvider === $provider['key'],
                                'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' => $selectedProvider !== $provider['key'],
                                'opacity-60' => !$provider['implemented'],
                            ])
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white">
                                        {{ $provider['name'] }}
                                    </h4>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $provider['description'] }}
                                    </p>
                                </div>
                                @if(!$provider['implemented'])
                                    <flux:badge size="sm" color="zinc">Coming Soon</flux:badge>
                                @elseif($selectedProvider === $provider['key'] && $isConnected)
                                    <flux:badge size="sm" color="green">Connected</flux:badge>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Configuration Form --}}
            @if($selectedProvider)
                @php
                    $providerInfo = $this->providers[$selectedProvider] ?? null;
                @endphp

                @if($providerInfo && $providerInfo['implemented'])
                    <flux:separator />

                    <div class="space-y-4">
                        <flux:heading size="sm">{{ $providerInfo['name'] }} Configuration</flux:heading>

                        @foreach($this->configFields as $field)
                            <div>
                                <flux:input
                                    wire:model="config.{{ $field['key'] }}"
                                    :label="$field['label']"
                                    :type="$field['type']"
                                    :placeholder="$field['placeholder'] ?? ''"
                                    autocomplete="off"
                                />
                                @if(isset($field['help']))
                                    <flux:description class="mt-1">{{ $field['help'] }}</flux:description>
                                @endif
                            </div>
                        @endforeach

                        {{-- Test Connection --}}
                        <div class="flex items-center gap-4 pt-2">
                            <flux:button
                                type="button"
                                variant="ghost"
                                wire:click="testConnection"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                                <span wire:loading wire:target="testConnection">Testing...</span>
                            </flux:button>

                            @if($testResult === 'success')
                                <span class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                                    <flux:icon.check-circle class="h-4 w-4" />
                                    Connection successful
                                </span>
                            @elseif($testResult === 'error')
                                <span class="flex items-center gap-1 text-sm text-red-600 dark:text-red-400">
                                    <flux:icon.x-circle class="h-4 w-4" />
                                    {{ $testError ?? 'Connection failed' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <flux:separator />

                    <div class="flex items-center gap-4">
                        <flux:button variant="primary" type="button" wire:click="save">
                            {{ __('Save Integration') }}
                        </flux:button>

                        @if($isConnected)
                            <flux:button variant="ghost" type="button" wire:click="disconnect" class="text-red-600 hover:text-red-700">
                                {{ __('Disconnect') }}
                            </flux:button>
                        @endif

                        <x-action-message class="me-3" on="integration-saved">
                            {{ __('Saved.') }}
                        </x-action-message>

                        <x-action-message class="me-3" on="integration-disconnected">
                            {{ __('Disconnected.') }}
                        </x-action-message>
                    </div>
                @else
                    <flux:separator />

                    <flux:callout variant="warning">
                        <flux:heading size="sm">{{ $providerInfo['name'] }} Integration Coming Soon</flux:heading>
                        <p class="mt-1 text-sm">
                            This integration is not yet available. We're working on adding support for {{ $providerInfo['name'] }}.
                        </p>
                    </flux:callout>
                @endif
            @endif

            {{-- Help Text --}}
            @if(!$selectedProvider)
                <flux:callout>
                    <p class="text-sm">
                        Connect a task manager to export accepted tasks directly. You can only connect one provider at a time.
                    </p>
                </flux:callout>
            @endif
        </div>
    </x-settings.layout>
</section>
