<?php

namespace App\Services\TaskIntegration;

use App\Models\Task;

class AsanaProvider implements TaskIntegrationProvider
{
    public function getKey(): string
    {
        return 'asana';
    }

    public function getName(): string
    {
        return 'Asana';
    }

    public function getDescription(): string
    {
        return 'Create tasks in an Asana project';
    }

    public function isImplemented(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function isConfigured(array $config): bool
    {
        return ! empty($config['access_token']) && ! empty($config['project_gid']);
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, placeholder?: string, help?: string}>
     */
    public function getConfigFields(): array
    {
        return [
            [
                'key' => 'access_token',
                'label' => 'Personal Access Token',
                'type' => 'password',
                'placeholder' => 'Your personal access token',
                'help' => 'Create a PAT at app.asana.com/0/developer-console',
            ],
            [
                'key' => 'project_gid',
                'label' => 'Project GID',
                'type' => 'text',
                'placeholder' => '1234567890',
                'help' => 'The project GID from the project URL',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function testConnection(array $config): bool|string
    {
        return 'Asana integration is coming soon';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createTask(Task $task, array $config): ExportResult
    {
        return ExportResult::failure('Asana integration is coming soon');
    }
}
