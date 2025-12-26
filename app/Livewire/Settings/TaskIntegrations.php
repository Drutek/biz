<?php

namespace App\Livewire\Settings;

use App\Services\TaskIntegration\TaskIntegrationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskIntegrations extends Component
{
    public ?string $selectedProvider = null;

    /** @var array<string, string> */
    public array $config = [];

    public bool $isConnected = false;

    public bool $isTesting = false;

    public ?string $testResult = null;

    public ?string $testError = null;

    protected TaskIntegrationManager $manager;

    public function boot(TaskIntegrationManager $manager): void
    {
        $this->manager = $manager;
    }

    public function mount(): void
    {
        $preferences = Auth::user()->getOrCreatePreferences();

        $this->selectedProvider = $preferences->task_integration_provider;
        $this->config = $preferences->getTaskIntegrationConfig();

        if ($this->selectedProvider) {
            $provider = $this->manager->getProvider($this->selectedProvider);
            $this->isConnected = $provider && $provider->isConfigured($this->config);
        }
    }

    public function selectProvider(string $key): void
    {
        $provider = $this->manager->getProvider($key);

        if (! $provider) {
            return;
        }

        // If selecting a different provider, clear config
        if ($this->selectedProvider !== $key) {
            $this->config = [];
            $this->isConnected = false;
            $this->testResult = null;
        }

        $this->selectedProvider = $key;
    }

    public function testConnection(): void
    {
        if (! $this->selectedProvider) {
            return;
        }

        $this->isTesting = true;
        $this->testResult = null;
        $this->testError = null;

        $provider = $this->manager->getProvider($this->selectedProvider);

        if (! $provider || ! $provider->isImplemented()) {
            $this->testResult = 'error';
            $this->testError = $provider ? $provider->getName().' is not yet implemented' : 'Provider not found';
            $this->isTesting = false;

            return;
        }

        $result = $this->manager->testConnection($this->selectedProvider, $this->config);

        if ($result === true) {
            $this->testResult = 'success';
        } else {
            $this->testResult = 'error';
            $this->testError = is_string($result) ? $result : 'Connection failed';
        }

        $this->isTesting = false;
    }

    public function save(): void
    {
        $preferences = Auth::user()->getOrCreatePreferences();

        $preferences->update([
            'task_integration_provider' => $this->selectedProvider,
            'task_integration_config' => $this->config,
        ]);

        if ($this->selectedProvider) {
            $provider = $this->manager->getProvider($this->selectedProvider);
            $this->isConnected = $provider && $provider->isConfigured($this->config);
        }

        $this->dispatch('integration-saved');
    }

    public function disconnect(): void
    {
        $preferences = Auth::user()->getOrCreatePreferences();

        $preferences->update([
            'task_integration_provider' => null,
            'task_integration_config' => null,
        ]);

        $this->selectedProvider = null;
        $this->config = [];
        $this->isConnected = false;
        $this->testResult = null;

        $this->dispatch('integration-disconnected');
    }

    /**
     * @return array<string, array{key: string, name: string, description: string, implemented: bool, fields: array<int, mixed>}>
     */
    public function getProvidersProperty(): array
    {
        $providers = [];

        foreach ($this->manager->getProviders() as $provider) {
            $providers[$provider->getKey()] = [
                'key' => $provider->getKey(),
                'name' => $provider->getName(),
                'description' => $provider->getDescription(),
                'implemented' => $provider->isImplemented(),
                'fields' => $provider->getConfigFields(),
            ];
        }

        return $providers;
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFieldsProperty(): array
    {
        if (! $this->selectedProvider) {
            return [];
        }

        $provider = $this->manager->getProvider($this->selectedProvider);

        return $provider ? $provider->getConfigFields() : [];
    }

    public function render(): View
    {
        return view('livewire.settings.task-integrations');
    }
}
