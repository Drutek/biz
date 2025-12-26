<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TaskIntegrationManager
{
    /**
     * @var array<string, TaskIntegrationProvider>
     */
    protected array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Register all available providers.
     */
    protected function registerProviders(): void
    {
        $providerClasses = [
            NotionProvider::class,
            TrelloProvider::class,
            JiraProvider::class,
            AsanaProvider::class,
        ];

        foreach ($providerClasses as $class) {
            $provider = new $class;
            $this->providers[$provider->getKey()] = $provider;
        }
    }

    /**
     * Get all available providers.
     *
     * @return array<string, TaskIntegrationProvider>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a specific provider by key.
     */
    public function getProvider(string $key): ?TaskIntegrationProvider
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * Get the active provider for a user.
     */
    public function getActiveProvider(User $user): ?TaskIntegrationProvider
    {
        $preferences = $user->getOrCreatePreferences();
        $providerKey = $preferences->task_integration_provider;

        if (! $providerKey) {
            return null;
        }

        return $this->getProvider($providerKey);
    }

    /**
     * Check if a user has a configured integration.
     */
    public function hasConfiguredIntegration(User $user): bool
    {
        $provider = $this->getActiveProvider($user);

        if (! $provider) {
            return false;
        }

        $config = $user->getOrCreatePreferences()->getTaskIntegrationConfig();

        return $provider->isConfigured($config);
    }

    /**
     * Get the display name of the user's active integration.
     */
    public function getActiveProviderName(User $user): ?string
    {
        $provider = $this->getActiveProvider($user);

        return $provider?->getName();
    }

    /**
     * Export a task to the user's configured external system.
     */
    public function exportTask(Task $task): ExportResult
    {
        $user = $task->user;
        $provider = $this->getActiveProvider($user);

        if (! $provider) {
            return ExportResult::failure('No task integration configured');
        }

        if (! $provider->isImplemented()) {
            return ExportResult::failure("{$provider->getName()} integration is coming soon");
        }

        $config = $user->getOrCreatePreferences()->getTaskIntegrationConfig();

        if (! $provider->isConfigured($config)) {
            return ExportResult::failure("{$provider->getName()} is not fully configured");
        }

        // Check if already exported
        if ($task->isExported()) {
            return ExportResult::failure('Task has already been exported');
        }

        $result = $provider->createTask($task, $config);

        if ($result->success) {
            // Store export metadata on the task
            $this->updateTaskMetadata($task, $provider->getKey(), $result);
        }

        return $result;
    }

    /**
     * Update task metadata with export information.
     */
    protected function updateTaskMetadata(Task $task, string $provider, ExportResult $result): void
    {
        $metadata = $task->metadata ?? [];
        $metadata['external_task'] = $result->toMetadata($provider);

        $task->update(['metadata' => $metadata]);

        Log::info('Task export metadata saved', [
            'task_id' => $task->id,
            'provider' => $provider,
            'external_id' => $result->externalId,
        ]);
    }

    /**
     * Test connection for a provider with given config.
     *
     * @param  array<string, mixed>  $config
     * @return bool|string Returns true on success, or error message string on failure
     */
    public function testConnection(string $providerKey, array $config): bool|string
    {
        $provider = $this->getProvider($providerKey);

        if (! $provider) {
            return 'Provider not found';
        }

        if (! $provider->isImplemented()) {
            return $provider->getName().' integration is coming soon';
        }

        return $provider->testConnection($config);
    }
}
