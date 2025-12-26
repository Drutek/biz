<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;

interface TaskIntegrationProvider
{
    /**
     * Get the provider's unique identifier.
     */
    public function getKey(): string;

    /**
     * Get the provider's display name.
     */
    public function getName(): string;

    /**
     * Get the provider's description.
     */
    public function getDescription(): string;

    /**
     * Check if this provider is fully implemented.
     */
    public function isImplemented(): bool;

    /**
     * Check if this provider is configured with valid credentials.
     *
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool;

    /**
     * Get the configuration fields required for this provider.
     *
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFields(): array;

    /**
     * Test the connection with the provided configuration.
     *
     * @param  array<string, mixed>  $config
     * @return bool|string Returns true on success, or error message string on failure
     */
    public function testConnection(array $config): bool|string;

    /**
     * Create a task in the external system.
     *
     * @param  array<string, mixed>  $config
     * @return ExportResult The result containing external ID and URL
     */
    public function createTask(Task $task, array $config): ExportResult;
}
